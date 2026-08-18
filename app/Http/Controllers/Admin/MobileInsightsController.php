<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MobilePushNotification;
use App\Services\MobileInsightsService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class MobileInsightsController extends Controller
{
    public function index(MobileInsightsService $insights): View
    {
        return view('admin.mobile-insights.index', [
            'pageTitle' => 'آمار اپلیکیشن',
            'summary' => $insights->summary(),
            'notifications' => MobilePushNotification::latest()->limit(20)->get(),
            'pushConfigured' => filled(config('services.firebase.project_id'))
                && is_string(config('services.firebase.credentials'))
                && is_readable(config('services.firebase.credentials')),
        ]);
    }

    public function summary(MobileInsightsService $insights): JsonResponse
    {
        return response()->json($insights->summary());
    }
}
