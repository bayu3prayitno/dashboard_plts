<?php

namespace App\Observers;

use App\Models\Setting;
use App\Support\SettingsCache;

class SettingObserver
{
    public function updated(Setting $setting): void
    {
        SettingsCache::forget($setting->key);
    }

    public function created(Setting $setting): void
    {
        SettingsCache::forget($setting->key);
    }
}
