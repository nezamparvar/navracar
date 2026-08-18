<?php

namespace App\Console\Commands;

use App\Models\MobileAnalyticsEvent;
use Illuminate\Console\Command;

class PruneMobileEngagement extends Command
{
    protected $signature = 'mobile:prune-engagement';

    protected $description = 'Delete consented mobile analytics events after the configured retention window';

    public function handle(): int
    {
        $days = max(1, (int) config('mobile.analytics_retention_days', 180));
        $deleted = MobileAnalyticsEvent::where('occurred_at', '<', now()->subDays($days))->delete();
        $this->info("Pruned {$deleted} mobile analytics events older than {$days} days.");

        return self::SUCCESS;
    }
}
