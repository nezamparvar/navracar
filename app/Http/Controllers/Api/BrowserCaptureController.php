<?php

namespace App\Http\Controllers\Api;

use App\Models\CarListing;
use App\Models\ImportQueue;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class BrowserCaptureController extends Controller
{
    public function store(Request $request)
    {
        $validated = $this->validatePayload($request->all());

        if (is_array($validated) && isset($validated['error'])) {
            return response()->json($validated, 422);
        }

        try {
            $queueItem = DB::transaction(function () use ($validated) {
                return $this->processCapture($validated);
            });

            return response()->json([
                'status' => 'success',
                'queue_item_id' => $queueItem->id,
                'duplicate_detected' => $this->checkDuplicate($validated),
                'review_url' => $this->buildReviewUrl($queueItem),
            ]);
        } catch (\Throwable $e) {
            \Log::error('Browser capture error', [
                'error' => $e->getMessage(),
                'source' => $validated['source'] ?? null,
            ]);

            return response()->json([
                'status' => 'error',
                'error' => 'Failed to process capture',
            ], 500);
        }
    }

    private function validatePayload(array $data): array
    {
        $rules = [
            'schema_version' => 'required|string',
            'source' => 'required|in:dubizzle,dubicars,yallamotor',
            'source_url' => 'required|url',
            'source_listing_id' => 'nullable|string',
            'captured_at' => 'required|date_format:Y-m-d\TH:i:sZ',
            'page_title' => 'nullable|string',
            'vehicle' => 'required|array',
            'vehicle.title' => 'nullable|string|max:255',
            'vehicle.make' => 'nullable|string|max:100',
            'vehicle.model' => 'nullable|string|max:100',
            'vehicle.trim' => 'nullable|string|max:100',
            'vehicle.year' => 'nullable|string|max:4',
            'vehicle.price_aed' => 'nullable|numeric|min:0',
            'vehicle.mileage_km' => 'nullable|string|max:100',
            'vehicle.fuel_type' => 'nullable|string|max:100',
            'vehicle.engine' => 'nullable|string|max:100',
            'vehicle.transmission' => 'nullable|string|max:100',
            'vehicle.body_type' => 'nullable|string|max:100',
            'vehicle.regional_specs' => 'nullable|string|max:255',
            'vehicle.color' => 'nullable|string|max:100',
            'vehicle.seller_type' => 'nullable|string|max:100',
            'vehicle.description' => 'nullable|string|max:5000',
            'images' => 'array',
            'images.*.url' => 'url|max:2000',
            'images.*.confidence' => 'in:high,medium,low',
            'diagnostics' => 'array',
        ];

        $validated = validator($data, $rules)->validate();

        if (empty($validated['vehicle']['title']) && empty($validated['vehicle']['make'])) {
            return [
                'error' => 'Must provide either title or make/model',
                'message' => 'عنوان یا برند/مدل ضروری است',
            ];
        }

        if (empty($validated['vehicle']['price_aed'])) {
            return [
                'error' => 'price_aed is required',
                'message' => 'قیمت ضروری است',
            ];
        }

        return $validated;
    }

    private function processCapture(array $validated): ImportQueue
    {
        $duplicate = $this->findDuplicate($validated);

        $queueItem = ImportQueue::create([
            'source' => $validated['source'],
            'source_listing_id' => $validated['source_listing_id'],
            'source_url' => $validated['source_url'],
            'source_method' => 'browser_extension',
            'status' => 'captured',
            'captured_data' => $validated,
            'canonical_url' => $this->normalizeUrl($validated['source_url']),
            'duplicate_detected_with' => $duplicate?->slug,
            'image_count' => count($validated['images'] ?? []),
            'diagnostics' => $validated['diagnostics'] ?? [],
        ]);

        return $queueItem;
    }

    private function findDuplicate(array $validated): ?CarListing
    {
        if (empty($validated['source_listing_id'])) {
            return null;
        }

        return CarListing::where('source_url', $validated['source_url'])
            ->orWhere(function ($query) use ($validated) {
                if (!empty($validated['vehicle']['make']) && !empty($validated['vehicle']['model'])) {
                    $query->where('make', $validated['vehicle']['make'])
                        ->where('model', $validated['vehicle']['model']);
                }
            })
            ->first();
    }

    private function checkDuplicate(array $validated): ?array
    {
        $duplicate = $this->findDuplicate($validated);

        if (!$duplicate) {
            return null;
        }

        return [
            'slug' => $duplicate->slug,
            'make' => $duplicate->make,
            'model' => $duplicate->model,
            'year' => $duplicate->model_year,
            'price_aed' => (float) $duplicate->price_aed,
        ];
    }

    private function normalizeUrl(string $url): string
    {
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? '';
        $path = $parsed['path'] ?? '';

        return $host.$path;
    }

    private function buildReviewUrl(ImportQueue $queueItem): string
    {
        $env = config('app.env');
        $baseUrl = $env === 'production'
            ? 'https://navracar.com'
            : 'https://navracar.com/staging';

        return $baseUrl.'/admin/import-queue/'.$queueItem->id;
    }
}
