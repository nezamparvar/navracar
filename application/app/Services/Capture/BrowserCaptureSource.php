<?php

namespace App\Services\Capture;

final class BrowserCaptureSource implements ListingCaptureSource
{
    public function capture(string $url, array $payload = []): array
    {
        $html = (string) ($payload['html'] ?? '');
        $structured = $payload['structured'] ?? null;
        if ($html === '' && ! is_array($structured)) {
            return ['status' => 'INVALID_CAPTURE', 'html' => null, 'url' => $url, 'warnings' => ['A browser capture must include structured data or sanitized HTML.']];
        }
        if ($html !== '' && strlen($html) > 5 * 1024 * 1024) {
            return ['status' => 'INVALID_CAPTURE', 'html' => null, 'url' => $url, 'warnings' => ['Captured HTML exceeds the 5 MB limit.']];
        }

        return ['status' => 'captured', 'html' => $html ?: null, 'url' => $url, 'warnings' => []];
    }
}

