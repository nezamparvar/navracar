<?php

namespace App\Services\Capture;

final class StructuredVehicleExtractor
{
    /** @return array<string,mixed> */
    public static function extract(string $html): array
    {
        $result = [];
        if (preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches)) {
            foreach ($matches[1] as $raw) {
                $decoded = json_decode(html_entity_decode(trim($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);
                $nodes = is_array($decoded) && array_is_list($decoded) ? $decoded : [$decoded];
                for ($index = 0; $index < count($nodes); $index++) {
                    $node = $nodes[$index];
                    if (! is_array($node)) {
                        continue;
                    }
                    if (isset($node['@graph']) && is_array($node['@graph'])) {
                        array_push($nodes, ...$node['@graph']);
                    }
                    $rawType = $node['@type'] ?? '';
                    $type = strtolower(is_array($rawType) ? implode(' ', $rawType) : (string) $rawType);
                    if (! str_contains($type, 'product') && ! str_contains($type, 'vehicle') && ! isset($node['offers'])) {
                        continue;
                    }
                    $result['title_en'] ??= $node['name'] ?? null;
                    $result['description_en'] ??= $node['description'] ?? null;
                    $result['price_aed'] ??= isset($node['offers']['price']) ? (float) $node['offers']['price'] : null;
                    $result['model_year'] ??= $node['vehicleModelDate'] ?? $node['productionDate'] ?? null;
                    $result['make'] ??= is_array($node['brand'] ?? null) ? ($node['brand']['name'] ?? null) : ($node['brand'] ?? null);
                    $result['model'] ??= $node['model'] ?? null;
                    $result['body_type'] ??= $node['bodyType'] ?? null;
                    $result['fuel_type'] ??= $node['fuelType'] ?? null;
                    $result['transmission_type'] ??= $node['vehicleTransmission'] ?? null;
                    $images = $node['image'] ?? [];
                    $result['images'] ??= is_array($images) ? array_values(array_filter($images, 'is_string')) : (is_string($images) ? [$images] : []);
                }
            }
        }
        foreach (['og:title' => 'title_en', 'og:description' => 'description_en'] as $property => $key) {
            if (!isset($result[$key]) && preg_match('/<meta[^>]+property=["\']'.preg_quote($property, '/').'["\'][^>]+content=["\']([^"\']*)/i', $html, $m)) {
                $result[$key] = html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }
        return array_filter($result, static fn ($v) => $v !== null && $v !== []);
    }
}
