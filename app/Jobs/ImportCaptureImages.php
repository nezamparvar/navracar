<?php

namespace App\Jobs;

use App\Models\ImportQueue;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportCaptureImages implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private ImportQueue $queueItem;
    private array $images;
    private int $maxRetries = 3;
    private int $downloadTimeout = 30;

    public function __construct(ImportQueue $queueItem, array $images)
    {
        $this->queueItem = $queueItem;
        $this->images = $images;
    }

    public function handle()
    {
        try {
            $downloadedImages = [];
            $client = new Client();

            foreach ($this->images as $index => $imageData) {
                $url = $imageData['url'] ?? $imageData;

                try {
                    $downloadedPath = $this->downloadImage($client, $url);

                    if ($downloadedPath) {
                        $downloadedImages[] = [
                            'url' => $url,
                            'stored_path' => $downloadedPath,
                            'confidence' => $imageData['confidence'] ?? 'medium',
                        ];

                        $this->queueItem->increment('images_imported');
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed to import single image', [
                        'queue_item_id' => $this->queueItem->id,
                        'image_url' => $url,
                        'error' => $e->getMessage(),
                    ]);

                    // Continue with next image instead of failing entire job
                    continue;
                }
            }

            // Update capture data with downloaded image paths
            $capturedData = $this->queueItem->captured_data;
            $capturedData['downloaded_images'] = $downloadedImages;
            $this->queueItem->update(['captured_data' => $capturedData]);

            // Update status once all images are processed
            if ($this->queueItem->images_imported >= $this->queueItem->image_count) {
                $this->queueItem->update(['status' => 'needs_review']);
            }
        } catch (\Throwable $e) {
            Log::error('ImportCaptureImages job failed', [
                'queue_item_id' => $this->queueItem->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            if ($this->attempts() < $this->maxRetries) {
                $this->release(delay: 60 * $this->attempts());
            } else {
                $this->queueItem->update(['status' => 'failed']);
            }
        }
    }

    private function downloadImage(Client $client, string $url): ?string
    {
        try {
            // Validate URL is accessible and not a security risk
            if (!$this->validateImageUrl($url)) {
                Log::warning('Invalid image URL', ['url' => $url]);

                return null;
            }

            // Download image with timeout
            $response = $client->get($url, [
                'timeout' => $this->downloadTimeout,
                'verify' => true,
                'headers' => [
                    'User-Agent' => 'NavraCar-Browser-Extension/1.0',
                ],
                'allow_redirects' => true,
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            // Validate content type is image
            $contentType = $response->getHeaderLine('content-type');
            if (!str_starts_with($contentType, 'image/')) {
                Log::warning('Non-image content type', [
                    'url' => $url,
                    'content_type' => $contentType,
                ]);

                return null;
            }

            // Validate file size (max 20MB)
            $body = (string) $response->getBody();
            if (strlen($body) > 20 * 1024 * 1024) {
                Log::warning('Image too large', [
                    'url' => $url,
                    'size' => strlen($body),
                ]);

                return null;
            }

            // Generate unique filename and store
            $extension = $this->getImageExtension($contentType, $url);
            $filename = Str::uuid().'.'.$extension;
            $path = "captures/{$this->queueItem->id}/".$filename;

            Storage::disk('public')->put($path, $body);

            return $path;
        } catch (RequestException $e) {
            Log::warning('Failed to download image', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function validateImageUrl(string $url): bool
    {
        // Parse URL to ensure it's valid
        $parsed = parse_url($url);

        if (!$parsed || !isset($parsed['scheme']) || !isset($parsed['host'])) {
            return false;
        }

        // Only allow http(s)
        if (!in_array($parsed['scheme'], ['http', 'https'])) {
            return false;
        }

        // Prevent localhost/private IPs (SSRF prevention)
        $host = $parsed['host'];
        if (in_array($host, ['localhost', '127.0.0.1', '0.0.0.0'])) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ip = $host;
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return false;
            }
        }

        return true;
    }

    private function getImageExtension(string $contentType, string $url): string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];

        if (isset($map[$contentType])) {
            return $map[$contentType];
        }

        // Fallback: try to extract from URL
        $path = parse_url($url, PHP_URL_PATH);
        if ($path) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                return $ext === 'jpeg' ? 'jpg' : $ext;
            }
        }

        return 'jpg';
    }
}
