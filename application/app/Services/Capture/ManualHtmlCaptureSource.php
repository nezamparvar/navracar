<?php

namespace App\Services\Capture;

final class ManualHtmlCaptureSource implements ListingCaptureSource
{
    public function capture(string $url, array $payload = []): array
    {
        $html = (string) ($payload['html'] ?? '');
        if ($html === '' || strlen($html) > 5 * 1024 * 1024) {
            return ['status' => 'INVALID_CAPTURE', 'html' => null, 'url' => $url, 'warnings' => ['HTML is empty or exceeds the 5 MB limit.']];
        }

        return ['status' => 'captured', 'html' => $html, 'url' => $url, 'warnings' => []];
    }
}

