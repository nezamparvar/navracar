<?php

namespace App\Services\Capture;

use App\Services\DubizzleParser;

final class DirectUrlCaptureSource implements ListingCaptureSource
{
    public function __construct(private readonly DubizzleParser $parser) {}

    public function capture(string $url, array $payload = []): array
    {
        $result = $this->parser->fetch($url);
        if ($result['html'] !== null) {
            return ['status' => 'captured', 'html' => $result['html'], 'url' => $url, 'warnings' => []];
        }

        return [
            'status' => $result['status'] ?? 'REMOTE_ACCESS_BLOCKED',
            'html' => null,
            'url' => $url,
            'warnings' => array_filter([$result['error'] ?? null]),
        ];
    }
}

