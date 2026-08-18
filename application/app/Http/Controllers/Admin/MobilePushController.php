<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendMobilePushNotification;
use App\Models\MobileAppInstallation;
use App\Models\MobilePushNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MobilePushController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:1000'],
            'target' => ['required', 'in:all'],
            'url' => ['nullable', 'string', 'max:500', 'regex:#^/(?:home|vehicles(?:/[A-Za-z0-9_-]+)?|requests|account|favorites)$#'],
        ]);
        $targets = MobileAppInstallation::where('notifications_consent', true)->whereNotNull('push_token_hash')->count();
        $configured = filled(config('services.firebase.project_id'))
            && is_string(config('services.firebase.credentials'))
            && is_readable(config('services.firebase.credentials'));

        $notification = MobilePushNotification::create([
            'created_by' => $request->user()->id,
            'title' => $data['title'],
            'body' => $data['body'],
            'data' => array_filter(['url' => $data['url'] ?? null]),
            'segment' => ['target' => 'all'],
            'status' => $configured ? 'queued' : 'disabled',
            'targeted_count' => $targets,
            'disabled_count' => $configured ? 0 : $targets,
            'queued_at' => $configured ? now() : null,
            'completed_at' => $configured ? null : now(),
        ]);

        if ($configured) {
            SendMobilePushNotification::dispatch($notification->id);
        }

        return redirect()->route('admin.mobile-insights.index')->with(
            $configured ? 'success' : 'warning',
            $configured ? 'اعلان در صف ارسال قرار گرفت.' : 'اعلان ثبت شد؛ ارسال FCM تا تنظیم اعتبارنامه استیج غیرفعال است.'
        );
    }
}
