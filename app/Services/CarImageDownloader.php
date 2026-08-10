<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CarImageDownloader
{
    private const MAX_IMAGES = 20;

    private const MAX_BYTES = 8 * 1024 * 1024; // 8MB

    /**
     * دانلود لیستی از URLهای عکس و ذخیره روی دیسک public.
     *
     * @param  array<int, string>  $sourceUrls
     * @return array<int, array{local_path: string, source_url: string}>
     */
    public function downloadAll(int $carListingId, array $sourceUrls): array
    {
        $saved = [];

        foreach (array_slice($sourceUrls, 0, self::MAX_IMAGES) as $i => $url) {
            $result = $this->downloadOne($carListingId, $url, $i);
            if ($result !== null) {
                $saved[] = $result;
            }
        }

        return $saved;
    }

    /**
     * @return array{local_path: string, source_url: string}|null
     */
    public function downloadOne(int $carListingId, string $url, int $index = 0): ?array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
                'Referer' => 'https://dubai.dubizzle.com/',
            ])->timeout(20)->get($url);
        } catch (\Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $body = $response->body();
        if (strlen($body) === 0 || strlen($body) > self::MAX_BYTES) {
            return null;
        }

        $contentType = $response->header('Content-Type');
        $ext = match (true) {
            str_contains((string) $contentType, 'png') => 'png',
            str_contains((string) $contentType, 'webp') => 'webp',
            default => 'jpg',
        };

        $filename = sprintf('%02d-%s.%s', $index, Str::lower(Str::random(8)), $ext);
        $path = "car-listings/{$carListingId}/{$filename}";

        Storage::disk('public')->put($path, $body);

        return ['local_path' => $path, 'source_url' => $url];
    }

    public function deleteAll(int $carListingId): void
    {
        Storage::disk('public')->deleteDirectory("car-listings/{$carListingId}");
    }
}
