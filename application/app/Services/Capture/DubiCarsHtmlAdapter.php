<?php

namespace App\Services\Capture;

final class DubiCarsHtmlAdapter extends SimpleMarketplaceHtmlAdapter
{
    public function platform(): string { return 'dubicars'; }

    public function supports(string $html, string $url): bool
    {
        return (bool) preg_match('/(^|\.)dubicars\.com$/i', (string) parse_url($url, PHP_URL_HOST))
            || (bool) preg_match('/dubicars/i', $html);
    }
}
