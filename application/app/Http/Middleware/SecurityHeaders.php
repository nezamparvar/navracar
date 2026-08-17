<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $csp = "default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'none'; form-action 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; img-src 'self' data: https:; connect-src 'self' https://vpic.nhtsa.dot.gov";
        if (app()->environment('production')) {
            $csp .= '; upgrade-insecure-requests';
        }

        $response->headers->set('Content-Security-Policy', $csp);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), geolocation=(), microphone=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        if (app()->environment('staging')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
            $metadataPath = base_path('.cpanel-release.json');
            if (is_file($metadataPath)) {
                $metadata = json_decode((string) file_get_contents($metadataPath), true);
                $candidate = is_array($metadata) ? ($metadata['release_candidate'] ?? null) : null;
                $source = is_array($metadata) ? ($metadata['source_commit'] ?? null) : null;
                if (is_string($candidate) && preg_match('/^rc-v[0-9A-Za-z.-]+$/', $candidate)) {
                    $response->headers->set('X-Navracar-Candidate', $candidate);
                }
                if (is_string($source) && preg_match('/^[0-9a-f]{40}$/', $source)) {
                    $response->headers->set('X-Navracar-Source', $source);
                }
            }
        }
        if ($request->isSecure() && app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
