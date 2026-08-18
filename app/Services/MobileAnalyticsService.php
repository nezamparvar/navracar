<?php

namespace App\Services;

use App\Models\MobileAppInstallation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class MobileAnalyticsService
{
    public const EVENT_NAMES = [
        'app_open', 'heartbeat', 'screen_view', 'search', 'vehicle_view', 'favorite', 'share',
        'pricing_calculate', 'quote_submit', 'contact_click', 'whatsapp_click', 'phone_click',
    ];

    private const SENSITIVE_KEYS = ['phone', 'email', 'password', 'token', 'secret', 'vin', 'imei', 'serial', 'mac', 'address'];

    /** @param array<int, array<string, mixed>> $events */
    public function record(MobileAppInstallation $installation, array $events): int
    {
        foreach ($events as $event) {
            if ($this->containsSensitiveKey($event['properties'] ?? [])) {
                throw ValidationException::withMessages(['events' => 'اطلاعات شخصی در رویدادهای آماری مجاز نیست.']);
            }
        }

        DB::transaction(function () use ($installation, $events): void {
            foreach ($events as $event) {
                $installation->events()->create([
                    'mobile_customer_id' => $installation->mobile_customer_id,
                    'name' => $event['name'],
                    'page' => $event['page'] ?? null,
                    'properties' => $event['properties'] ?? null,
                    'occurred_at' => $event['occurred_at'],
                ]);
            }

            $installation->forceFill(['last_seen_at' => now()])->save();
        });

        return count($events);
    }

    private function containsSensitiveKey(mixed $value): bool
    {
        if (! is_array($value)) {
            return false;
        }

        foreach ($value as $key => $nested) {
            if (is_string($key)) {
                $normalized = strtolower(str_replace(['-', ' '], '_', $key));
                foreach (self::SENSITIVE_KEYS as $sensitive) {
                    if ($normalized === $sensitive || str_ends_with($normalized, '_'.$sensitive)) {
                        return true;
                    }
                }
            }
            if ($this->containsSensitiveKey($nested)) {
                return true;
            }
        }

        return false;
    }
}
