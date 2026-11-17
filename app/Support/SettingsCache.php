<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsCache
{
    private const CACHE_TTL = 3600; // 1 hour

    private const CACHE_PREFIX = 'setting_';

    public static function get(string $key, ?string $default = null): ?string
    {
        return Cache::remember(self::CACHE_PREFIX . $key, self::CACHE_TTL, function () use ($key, $default) {
            return Setting::find($key)?->value ?? $default;
        });
    }

    public static function forget(string $key): void
    {
        Cache::forget(self::CACHE_PREFIX . $key);
    }

    public static function flush(): void
    {
        $keys = [
            'timezone',
            'offline_timeout_minutes',
            'data_retention_days',
            'beacon_interval_minutes',
        ];

        foreach ($keys as $key) {
            self::forget($key);
        }
    }
}
