<?php

namespace App\Services\Capture;

final class DubiCarsHtmlAdapter implements MarketplaceHtmlAdapter
{
    public function platform(): string { return 'dubicars'; }

    public function supports(string $html, string $url): bool
    {
        return (bool) preg_match('/(^|\.)dubicars\.com$/i', (string) parse_url($url, PHP_URL_HOST))
            || (bool) preg_match('/dubicars/i', $html);
    }

    public function parse(string $html, string $url): array
    {
        $data = StructuredVehicleExtractor::extract($html);
        $data['source_url'] = $url;
        return $data;
    }
}
