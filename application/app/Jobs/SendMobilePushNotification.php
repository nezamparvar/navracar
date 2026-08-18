<?php

namespace App\Jobs;

use App\Models\MobileAppInstallation;
use App\Models\MobilePushDelivery;
use App\Models\MobilePushNotification;
use App\Services\FcmClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SendMobilePushNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public readonly int $notificationId) {}

    public function handle(FcmClient $fcm): void
    {
        $notification = MobilePushNotification::findOrFail($this->notificationId);
        $targets = MobileAppInstallation::query()
            ->where('notifications_consent', true)
            ->whereNotNull('push_token_hash');
        $notification->update(['status' => 'sending', 'targeted_count' => $targets->count()]);

        $targets->chunkById(100, function ($installations) use ($notification, $fcm): void {
            foreach ($installations as $installation) {
                $delivery = MobilePushDelivery::firstOrCreate([
                    'mobile_push_notification_id' => $notification->id,
                    'mobile_app_installation_id' => $installation->id,
                ]);
                if ($delivery->status === 'sent') {
                    continue;
                }

                try {
                    $result = $fcm->send((string) $installation->push_token, $notification->title, $notification->body, [
                        ...($notification->data ?? []),
                        'notification_id' => $notification->id,
                    ]);
                    if ($result->successful) {
                        $delivery->update(['status' => 'sent', 'error_code' => null, 'sent_at' => now()]);
                    } else {
                        $delivery->update(['status' => 'failed', 'error_code' => $result->errorCode ?: 'FCM_ERROR']);
                        if ($result->invalidToken) {
                            $installation->update(['push_token' => null, 'push_token_hash' => null, 'push_token_updated_at' => null]);
                        }
                    }
                } catch (Throwable) {
                    $delivery->update(['status' => 'failed', 'error_code' => 'FCM_EXCEPTION']);
                }
            }
        });

        $notification->update([
            'status' => 'completed',
            'sent_count' => $notification->deliveries()->where('status', 'sent')->count(),
            'failed_count' => $notification->deliveries()->where('status', 'failed')->count(),
            'completed_at' => now(),
        ]);
    }
}
