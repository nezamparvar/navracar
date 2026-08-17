<?php

namespace App\Services\Capture;

abstract class SimpleMarketplaceHtmlAdapter implements MarketplaceHtmlAdapter
{
    public function parse(string $html, string $url): array
    {
        $data = StructuredVehicleExtractor::extract($html);
        if (!isset($data['source_url'])) {
            $data['source_url'] = $url;
        }
        return array_filter($data, static fn ($v) => $v !== null && $v !== []);
    }
}
