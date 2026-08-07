<?php

namespace App\Services;

use App\Support\ActivityLogger;
use Illuminate\Support\Facades\Http;

/**
 * Resolves country/city from an IP via ip-api.com (no key required). Fails
 * silently — geolocation is a nice-to-have and must never block a request.
 */
class GeoLookupService
{
    public function lookup(?string $ip): array
    {
        $result = ['country' => null, 'city' => null];

        if (! $ip || $ip === '127.0.0.1' || $ip === '::1' || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
            return $result;
        }

        try {
            $response = Http::timeout(2)->get("http://ip-api.com/json/{$ip}", [
                'fields' => 'status,country,city',
            ]);

            if ($response->ok() && $response->json('status') === 'success') {
                $result['country'] = $response->json('country');
                $result['city'] = $response->json('city');
            }
        } catch (\Throwable $e) {
            ActivityLogger::error('geo lookup failed (non-fatal)', ['error' => $e->getMessage()]);
        }

        return $result;
    }
}
