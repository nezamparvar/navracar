<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\MobilePushDelivery;
use App\Models\MobilePushNotification;
use App\Services\MobileInstallationAuthenticator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PushOpenedController extends Controller
{
    public function __invoke(Request $request, MobilePushNotification $notification, MobileInstallationAuthenticator $auth): Response
    {
        $installation = $auth->resolve($request);
        abort_unless($installation, 403, 'نصب برنامه احراز نشد.');

        DB::transaction(function () use ($notification, $installation): void {
            $delivery = MobilePushDelivery::where('mobile_push_notification_id', $notification->id)
                ->where('mobile_app_installation_id', $installation->id)
                ->lockForUpdate()->first();
            if ($delivery && $delivery->opened_at === null) {
                $delivery->update(['opened_at' => now()]);
                $notification->increment('opened_count');
            }
        });

        return response()->noContent();
    }
}
