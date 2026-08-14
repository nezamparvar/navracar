<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

/**
 * Thin wrapper around the dedicated "activity" log channel, mirroring the
 * legacy navarakar_log()/navarakar_log_tail() helpers: every important event
 * (logins, lead updates, endpoint failures) is recorded with request context
 * so the admin "activity log" page can show a readable timeline.
 */
class ActivityLogger
{
    public static function info(string $message, array $context = []): void
    {
        static::write('info', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        static::write('error', $message, $context);
    }

    protected static function write(string $level, string $message, array $context): void
    {
        $line = sprintf(
            '[%s] [%s] [%s] %s',
            now()->format('Y-m-d H:i:s'),
            Request::ip() ?? '-',
            Request::path() ?? 'cli',
            $message
        );

        if (! empty($context)) {
            $line .= ' | '.json_encode($context, JSON_UNESCAPED_UNICODE);
        }

        Log::channel('activity')->{$level}($line);
    }

    /**
     * Read the most recent $lines log lines across the rotated activity-*.log
     * files, newest first.
     */
    public static function tail(int $lines = 300): array
    {
        $files = collect(glob(storage_path('logs/activity-*.log')))
            ->sortDesc()
            ->values();

        $collected = [];

        foreach ($files as $file) {
            $content = @file($file, FILE_IGNORE_NEW_LINES);
            if (! $content) {
                continue;
            }

            $collected = array_merge($collected, array_reverse($content));

            if (count($collected) >= $lines) {
                break;
            }
        }

        return array_slice($collected, 0, $lines);
    }
}
