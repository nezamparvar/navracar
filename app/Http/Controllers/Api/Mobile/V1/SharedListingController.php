<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\ImportQueueItem;
use App\Services\OutboundUrlGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SharedListingController extends Controller
{
    public function __invoke(Request $request, OutboundUrlGuard $guard): JsonResponse
    {
        $data = $request->validate(['url' => ['required', 'url:http,https', 'max:2000']]);
        $host = strtolower((string) parse_url($data['url'], PHP_URL_HOST));
        $source = $this->source($host);
        if (! $source || ! $guard->allows($data['url'], [$host])) {
            throw ValidationException::withMessages(['url' => 'این نشانی از بازارهای پشتیبانی‌شده نیست.']);
        }

        $item = ImportQueueItem::create([
            'source' => $source,
            'source_platform' => $source,
            'capture_method' => 'android_share',
            'source_url' => $data['url'],
            'status' => 'pending',
            'payload_json' => ['schema_version' => 'navracar.android-share.v1', 'mobile_customer_id' => $request->attributes->get('mobile_customer')->id],
            'warnings_json' => ['Shared URL requires the existing server-side capture/review flow.'],
        ]);

        return response()->json(['id' => $item->id, 'status' => $item->status, 'source' => $source], 202);
    }

    private function source(string $host): ?string
    {
        foreach (['dubizzle' => 'dubizzle.com', 'dubicars' => 'dubicars.com', 'yallamotor' => 'yallamotor.com'] as $source => $domain) {
            if ($host === $domain || str_ends_with($host, '.'.$domain)) return $source;
        }

        return null;
    }
}
