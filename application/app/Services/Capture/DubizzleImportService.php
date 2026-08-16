<?php

namespace App\Services\Capture;

use App\Services\DubizzleParser;

final class DubizzleImportService
{
    public function __construct(private readonly DubizzleParser $parser) {}

    /** @return array{status:string,data:array<string,mixed>,warnings:array<int,string>} */
    public function import(ListingCaptureSource $source, string $url, array $payload = []): array
    {
        $capture = $source->capture($url, $payload);
        if ($capture['html'] === null) {
            return ['status' => $capture['status'], 'data' => [], 'warnings' => $capture['warnings']];
        }

        return [
            'status' => 'parsed',
            'data' => $this->parser->parse($capture['html'], $capture['url']),
            'warnings' => $capture['warnings'],
        ];
    }
}

