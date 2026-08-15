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
    public function store(Request $request): JsonResponse
    {
        $token = $this->extractBearerToken($request);
        if (!$token) {
            throw ValidationException::withMessages([
                'authorization' => 'Missing or invalid Bearer token.',
            ]);
        }

        $pairing = BrowserExtensionPairing::where('token', $token)->first();
        if (!$pairing || !$pairing->isActive()) {
            throw ValidationException::withMessages([
                'authorization' => 'Invalid or expired authentication token.',
            ]);
        }

        $data = $request->validate([
            'source' => ['required', 'in:dubizzle,dubicars,yallamotor'],
            'source_url' => ['required', 'url', 'max:2000'],
            'vehicle' => ['required', 'array'],
            'images' => ['nullable', 'array'],
            'diagnostics' => ['nullable', 'array'],
        ]);

        $queueItem = ImportQueueItem::create([
            'user_id' => $pairing->admin_user_id,
            'source' => $data['source'],
            'source_platform' => $data['source'],
            'capture_method' => 'browser_extension',
            'source_url' => $data['source_url'],
            'status' => 'needs_review',
            'payload_json' => [
                'schema_version' => 'navracar.capture.v1',
                'vehicle' => $data['vehicle'] ?? [],
                'images' => $data['images'] ?? [],
            ],
            'parsed_json' => $data['vehicle'] ?? [],
            'warnings_json' => [],
        ]);

        $pairing->update(['last_used_at' => now()]);

        $reviewUrl = route('admin.import-queue.show', $queueItem->id);

        return response()->json([
            'status' => 'success',
            'queue_item_id' => $queueItem->id,
            'review_url' => $reviewUrl,
            'duplicate_detected' => false,
            'message' => 'Capture received and queued for review',
        ], 200);
    }

    protected function extractBearerToken(Request $request): ?string
    {
        $header = $request->header('Authorization');
        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return null;
        }
        return substr($header, 7);
    }
}
