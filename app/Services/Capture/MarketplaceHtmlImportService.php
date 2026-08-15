<?php

namespace App\Services\Capture;

use InvalidArgumentException;

final class MarketplaceHtmlImportService
{
    /** @param iterable<MarketplaceHtmlAdapter> $adapters */
    public function __construct(private readonly iterable $adapters) {}

    /** @return array{status:string,source_platform:string,capture_method:string,data:array<string,mixed>,warnings:array<int,string>} */
    public function import(string $html, string $url): array
    {
        if ($html === '' || strlen($html) > 5 * 1024 * 1024) {
            throw new InvalidArgumentException('Manual HTML is empty or exceeds the 5 MB limit.');
        }
        if (preg_match('/(?:cookie|authorization|session|access[_-]?token|password)\s*[:=]/i', $html)) {
            throw new InvalidArgumentException('Credentials and browser session material are not accepted.');
        }
        $matches = array_values(array_filter(iterator_to_array($this->adapters), fn (MarketplaceHtmlAdapter $a) => $a->supports($html, $url)));
        if (count($matches) !== 1) {
            throw new InvalidArgumentException(count($matches) === 0 ? 'Unsupported marketplace HTML.' : 'Marketplace source is ambiguous.');
        }
        $adapter = $matches[0];
        $data = $adapter->parse($html, $url);
        return ['status' => empty($data) ? 'needs_review' : 'parsed', 'source_platform' => $adapter->platform(), 'capture_method' => 'manual_html', 'data' => $data, 'warnings' => empty($data) ? ['No supported vehicle fields were found.'] : []];
    }
}
