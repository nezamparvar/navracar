<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FcmAccessTokenProvider
{
    public function accessToken(): string
    {
        $credentials = $this->credentials();
        $cacheKey = 'fcm.oauth.'.hash('sha256', $credentials['client_email']);

        return Cache::remember($cacheKey, now()->addMinutes(50), function () use ($credentials): string {
            $now = time();
            $assertion = $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR)).'.'
                .$this->base64Url(json_encode([
                    'iss' => $credentials['client_email'],
                    'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                    'aud' => $credentials['token_uri'],
                    'iat' => $now,
                    'exp' => $now + 3600,
                ], JSON_THROW_ON_ERROR));
            if (! openssl_sign($assertion, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
                throw new RuntimeException('امضای اعتبارنامه FCM ناموفق بود.');
            }
            $assertion .= '.'.$this->base64Url($signature);

            $response = Http::asForm()->timeout(10)->post($credentials['token_uri'], [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ]);
            if (! $response->successful() || ! is_string($response->json('access_token'))) {
                throw new RuntimeException('دریافت access token از FCM ناموفق بود.');
            }

            return $response->json('access_token');
        });
    }

    /** @return array{client_email: string, private_key: string, token_uri: string} */
    private function credentials(): array
    {
        $path = config('services.firebase.credentials');
        if (! is_string($path) || $path === '' || ! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('اعتبارنامه خواندنی FCM تنظیم نشده است.');
        }
        $realPath = realpath($path);
        $publicPath = realpath(public_path());
        if (! is_string($realPath) || ($publicPath && str_starts_with(strtolower($realPath), strtolower($publicPath.DIRECTORY_SEPARATOR)))) {
            throw new RuntimeException('اعتبارنامه FCM باید خارج از مسیر public باشد.');
        }
        $decoded = json_decode((string) file_get_contents($realPath), true);
        foreach (['client_email', 'private_key', 'token_uri'] as $key) {
            if (! is_array($decoded) || ! isset($decoded[$key]) || ! is_string($decoded[$key]) || $decoded[$key] === '') {
                throw new RuntimeException('ساختار اعتبارنامه FCM معتبر نیست.');
            }
        }

        return [
            'client_email' => $decoded['client_email'],
            'private_key' => $decoded['private_key'],
            'token_uri' => $decoded['token_uri'],
        ];
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
