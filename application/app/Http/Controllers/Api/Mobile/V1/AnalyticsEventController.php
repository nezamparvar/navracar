<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Services\MobileAnalyticsService;
use App\Services\MobileInstallationAuthenticator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AnalyticsEventController extends Controller
{
    public function store(Request $request, MobileInstallationAuthenticator $auth, MobileAnalyticsService $analytics): JsonResponse
    {
        $data = $request->validate([
            'events' => ['required', 'array', 'min:1', 'max:25'],
            'events.*.name' => ['required', 'string', Rule::in(MobileAnalyticsService::EVENT_NAMES)],
            'events.*.page' => ['sometimes', 'nullable', 'string', 'max:100'],
            'events.*.occurred_at' => ['required', 'date', 'before_or_equal:now'],
            'events.*.properties' => ['sometimes', 'nullable', 'array', 'max:20'],
        ]);
        $installation = $auth->resolve($request);
        if (! $installation) {
            return response()->json(['message' => 'نصب برنامه احراز نشد.'], 403);
        }
        if (! $installation->analytics_consent) {
            return response()->json(['message' => 'رضایت آمار استفاده فعال نیست.'], 409);
        }

        return response()->json(['accepted' => $analytics->record($installation, $data['events'])], 202);
    }
}
