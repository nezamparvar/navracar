<?php

namespace App\Services\Capture;

interface MarketplaceHtmlAdapter
{
    public function platform(): string;

    public function supports(string $html, string $url): bool;

    /** @return array<string,mixed> */
    public function parse(string $html, string $url): array;
}
