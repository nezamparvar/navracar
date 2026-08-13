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

        if (! $ip || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return $result;
        }

        try {
            $response = Http::withoutRedirecting()->timeout(2)->get('https://ipapi.co/'.rawurlencode($ip).'/json/');

            if ($response->ok() && ! $response->json('error')) {
                $result['country'] = $response->json('country_name');
                $result['city'] = $response->json('city');
            }
        } catch (\Throwable $e) {
            ActivityLogger::error('geo lookup failed (non-fatal)', ['error' => $e->getMessage()]);
        }

        return $result;
    }
}
