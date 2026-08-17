<?php

namespace App\Services\Capture;

final class YallaMotorHtmlAdapter extends SimpleMarketplaceHtmlAdapter
{
    public function platform(): string { return 'yallamotor'; }

    public function supports(string $html, string $url): bool
    {
        return (bool) preg_match('/(^|\.)yallamotor\.com$/i', (string) parse_url($url, PHP_URL_HOST))
            || (bool) preg_match('/yalla\s*motor|yallamotor/i', $html);
    }
}
