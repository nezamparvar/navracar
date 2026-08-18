<?php

namespace App\Services;

use App\Models\MobileAnalyticsEvent;
use App\Models\MobileAppInstallation;

final class MobileInsightsService
{
    /** @return array<string, mixed> */
    public function summary(int $days = 30): array
    {
        $since = now()->subDays($days);
        $eventQuery = MobileAnalyticsEvent::where('occurred_at', '>=', $since);
        $searches = [];
        $eventNames = [];
        $daily = [];
        $contactCounts = ['whatsapp_click' => 0, 'phone_click' => 0, 'contact_click' => 0];
        foreach ((clone $eventQuery)->select(['id', 'mobile_app_installation_id', 'name', 'properties', 'occurred_at'])->lazyById(500) as $event) {
            $eventNames[$event->name] = ($eventNames[$event->name] ?? 0) + 1;
            if ($event->name === 'search' && filled($event->properties['query'] ?? null)) {
                $label = trim((string) $event->properties['query']);
                $searches[$label] = ($searches[$label] ?? 0) + 1;
            }
            if (isset($contactCounts[$event->name])) {
                $contactCounts[$event->name]++;
            }
            $date = $event->occurred_at->format('Y-m-d');
            $daily[$date][$event->mobile_app_installation_id] = true;
        }

        $devices = [];
        $locations = [];
        $sources = [];
        foreach (MobileAppInstallation::select(['id', 'device_manufacturer', 'device_model', 'country', 'city', 'acquisition_source'])->lazyById(500) as $installation) {
            $this->increment($devices, trim(($installation->device_manufacturer ?: '').' '.($installation->device_model ?: '')));
            $this->increment($locations, trim(($installation->city ?: '').(($installation->city && $installation->country) ? '، ' : '').($installation->country ?: '')));
            $this->increment($sources, trim((string) $installation->acquisition_source));
        }

        $contactLabels = ['whatsapp_click' => 'WhatsApp', 'phone_click' => 'تماس تلفنی', 'contact_click' => 'فرم تماس'];
        ksort($daily);

        return [
            'period_days' => $days,
            'online_now' => MobileAppInstallation::where('analytics_consent', true)->where('last_seen_at', '>=', now()->subMinutes(2))->count(),
            'total_installations' => MobileAppInstallation::count(),
            'active_installations' => (clone $eventQuery)->distinct()->count('mobile_app_installation_id'),
            'analytics_opt_in' => MobileAppInstallation::where('analytics_consent', true)->count(),
            'push_opt_in' => MobileAppInstallation::where('notifications_consent', true)->count(),
            'event_count' => (clone $eventQuery)->count(),
            'top_searches' => $this->ranking($searches),
            'top_devices' => $this->ranking($devices),
            'top_locations' => $this->ranking($locations),
            'top_sources' => $this->ranking($sources),
            'contact_actions' => collect($contactLabels)->map(fn ($label, $name) => ['label' => $label, 'count' => $contactCounts[$name]])->values()->all(),
            'top_events' => $this->ranking($eventNames),
            'daily_active' => collect($daily)->map(fn ($ids, $date) => ['date' => $date, 'count' => count($ids)])->values()->all(),
        ];
    }

    /** @param array<string, int> $counts */
    private function increment(array &$counts, string $label): void
    {
        if ($label !== '') {
            $counts[$label] = ($counts[$label] ?? 0) + 1;
        }
    }

    /** @param array<string, int> $counts
     * @return array<int, array{label: string, count: int}>
     */
    private function ranking(array $counts): array
    {
        arsort($counts);

        return collect(array_slice($counts, 0, 10, true))
            ->map(fn ($count, $label) => ['label' => (string) $label, 'count' => $count])->values()->all();
    }
}
