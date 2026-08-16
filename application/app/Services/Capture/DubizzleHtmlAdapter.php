<?php

namespace App\Services\Capture;

use App\Services\DubizzleParser;

final class DubizzleHtmlAdapter implements MarketplaceHtmlAdapter
{
    public function __construct(private readonly DubizzleParser $parser) {}

    public function platform(): string { return 'dubizzle'; }

    public function supports(string $html, string $url): bool
    {
        return (bool) preg_match('/(^|\.)dubizzle\.com$/i', (string) parse_url($url, PHP_URL_HOST))
            || (bool) preg_match('/dubizzle/i', $html);
    }

    public function parse(string $html, string $url): array
    {
        return array_filter(array_merge(StructuredVehicleExtractor::extract($html), $this->parser->parse($html, $url)), static fn ($v) => $v !== null && $v !== []);
    }
}
