<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public const FREE_RATE = 'free_rate';

    public const CUSTOMS_RATE = 'customs_rate';

    public const DEFAULTS = [
        self::FREE_RATE => '51000',
        self::CUSTOMS_RATE => '35688',
    ];

    public static function get(string $key, ?string $default = null): string
    {
        return Cache::rememberForever("setting.$key", function () use ($key, $default) {
            return static::query()->where('key', $key)->value('value')
                ?? $default
                ?? self::DEFAULTS[$key]
                ?? '';
        });
    }

    public static function set(string $key, string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting.$key");
    }
}
