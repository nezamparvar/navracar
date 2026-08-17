<?php

namespace App\Http\Controllers\Api\BrowserCapture;

use App\Http\Controllers\Controller;
use App\Models\BrowserExtensionPairing;
use App\Models\ImportQueueItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CaptureController extends Controller
{
    private const SOURCE_HOSTS = [
        'dubizzle' => ['dubizzle.com'],
        'dubicars' => ['dubicars.com'],
        'yallamotor' => ['yallamotor.com'],
    ];

    public function store(Request $request): JsonResponse
    {
        $token = $request->bearerToken();
        $pairing = is_string($token) && preg_match('/^[a-f0-9]{64}$/', $token)
            ? BrowserExtensionPairing::activeForToken($token)
            : null;
        if (! $pairing) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        if ($request->hasAny(['cookies', 'headers', 'authorization', 'session', 'password', 'token'])) {
            throw ValidationException::withMessages(['capture' => 'Browser credentials are never accepted.']);
        }

        $data = $request->validate([
            'schema_version' => ['nullable', 'in:navracar.capture.v1'],
            'source' => ['required', 'in:dubizzle,dubicars,yallamotor'],
            'source_url' => ['required', 'url:http,https', 'max:2000'],
            'source_listing_id' => ['nullable', 'string', 'max:255'],
            'captured_at' => ['nullable', 'date'],
            'page_title' => ['nullable', 'string', 'max:500'],
            'vehicle' => ['required', 'array:title,make,model,year,price_aed,mileage_km,fuel_type,transmission,body_type,color,description,engine_capacity_cc,engine,regional_specs,steering_side,exterior_color,interior_color,seller_type,warranty,horsepower,no_of_cylinders,doors,seating_capacity,trim,posted_on'],
            'vehicle.title' => ['nullable', 'string', 'max:500'],
            'vehicle.make' => ['nullable', 'string', 'max:100'],
            'vehicle.model' => ['nullable', 'string', 'max:100'],
            'vehicle.year' => ['nullable', 'string', 'max:10'],
            'vehicle.price_aed' => ['nullable', 'numeric', 'min:0', 'max:1000000000'],
            'vehicle.mileage_km' => ['nullable', 'string', 'max:50'],
            'vehicle.fuel_type' => ['nullable', 'string', 'max:100'],
            'vehicle.transmission' => ['nullable', 'string', 'max:100'],
            'vehicle.body_type' => ['nullable', 'string', 'max:100'],
            'vehicle.color' => ['nullable', 'string', 'max:100'],
            'vehicle.description' => ['nullable', 'string', 'max:10000'],
            'vehicle.engine_capacity_cc' => ['nullable', 'string', 'max:100'],
            'vehicle.engine' => ['nullable', 'string', 'max:100'],
            'vehicle.regional_specs' => ['nullable', 'string', 'max:100'],
            'vehicle.steering_side' => ['nullable', 'string', 'max:100'],
            'vehicle.exterior_color' => ['nullable', 'string', 'max:100'],
            'vehicle.interior_color' => ['nullable', 'string', 'max:100'],
            'vehicle.seller_type' => ['nullable', 'string', 'max:100'],
            'vehicle.warranty' => ['nullable', 'string', 'max:100'],
            'vehicle.horsepower' => ['nullable', 'string', 'max:100'],
            'vehicle.no_of_cylinders' => ['nullable', 'string', 'max:20'],
            'vehicle.doors' => ['nullable', 'string', 'max:20'],
            'vehicle.seating_capacity' => ['nullable', 'string', 'max:20'],
            'vehicle.trim' => ['nullable', 'string', 'max:255'],
            'vehicle.posted_on' => ['nullable', 'string', 'max:100'],
            'images' => ['nullable', 'array', 'max:20'],
            'images.*.url' => ['required_with:images', 'url:http,https', 'max:2000'],
            'images.*.confidence' => ['nullable', 'in:high,medium,low'],
            'diagnostics' => ['nullable', 'array', 'max:50'],
        ]);
        $this->assertSourceHost($data['source'], $data['source_url']);
        $this->assertSafeDiagnostics($data['diagnostics'] ?? []);
        $this->assertImageHosts($data['source'], $data['images'] ?? []);
        $vehicle = $data['vehicle'];
        $vehicle['engine_capacity_cc'] = $vehicle['engine_capacity_cc'] ?? $vehicle['engine'] ?? null;
        unset($vehicle['engine']);

        $duplicate = ImportQueueItem::where('source', $data['source'])
            ->where('source_url', $data['source_url'])
            ->latest('id')
            ->first();
        $images = array_values($data['images'] ?? []);
        $warnings = $duplicate ? ['Possible duplicate of queue item #'.$duplicate->id] : [];
        $queueItem = ImportQueueItem::create([
            'user_id' => $pairing->admin_user_id,
            'source' => $data['source'],
            'source_platform' => $data['source'],
            'capture_method' => 'browser_extension',
            'source_url' => $data['source_url'],
            'status' => 'needs_review',
            'payload_json' => [
                'schema_version' => 'navracar.capture.v1',
                'source_listing_id' => $data['source_listing_id'] ?? null,
                'captured_at' => $data['captured_at'] ?? null,
                'page_title' => $data['page_title'] ?? null,
                'vehicle' => $vehicle,
                'images' => $images,
                'diagnostics' => $data['diagnostics'] ?? [],
                'duplicate_queue_item_id' => $duplicate?->id,
            ],
            'parsed_json' => $vehicle,
            'warnings_json' => $warnings,
            'confidence' => null,
        ]);

        $pairing->update(['last_used_at' => now()]);

        return response()->json([
            'status' => 'success',
            'queue_item_id' => $queueItem->id,
            'review_url' => route('admin.import-queue.show', $queueItem),
            'duplicate_detected' => $duplicate !== null,
            'message' => 'Capture received and queued for review.',
        ]);
    }

    private function assertSourceHost(string $source, string $url): void
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $allowed = collect(self::SOURCE_HOSTS[$source])->contains(
            fn (string $domain) => $host === $domain || str_ends_with($host, '.'.$domain),
        );
        if (! $allowed) {
            throw ValidationException::withMessages(['source_url' => 'URL host does not match the selected marketplace.']);
        }
    }

    private function assertSafeDiagnostics(array $diagnostics): void
    {
        if (strlen((string) json_encode($diagnostics)) > 65536) {
            throw ValidationException::withMessages(['diagnostics' => 'Diagnostics payload is too large.']);
        }
        $walk = function (array $values) use (&$walk): bool {
            foreach ($values as $key => $value) {
                if (is_string($key) && preg_match('/token|password|authorization|cookie|secret|credential|session/i', $key)) {
                    return false;
                }
                if (is_array($value) && ! $walk($value)) {
                    return false;
                }
            }

            return true;
        };
        if (! $walk($diagnostics)) {
            throw ValidationException::withMessages(['diagnostics' => 'Diagnostics must not contain credentials or secrets.']);
        }
    }

    private function assertImageHosts(string $source, array $images): void
    {
        $allowed = match ($source) {
            'dubizzle' => ['dbz-images.dubizzle.com'],
            'dubicars' => ['dubicars.com'],
            'yallamotor' => ['b8cdn.com'],
        };
        foreach ($images as $image) {
            $host = strtolower((string) parse_url($image['url'] ?? '', PHP_URL_HOST));
            $valid = collect($allowed)->contains(fn (string $domain) => $host === $domain || str_ends_with($host, '.'.$domain));
            if (! $valid) {
                throw ValidationException::withMessages(['images' => 'Image URL host does not match the selected marketplace.']);
            }
        }
    }
}
