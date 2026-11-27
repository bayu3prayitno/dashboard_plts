<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Deteksi perangkat offline (PRD 4.4). Butuh scheduler: `php artisan schedule:work` atau cron `schedule:run`.
Schedule::command('alerts:check-offline')->everyMinute();

// Status online node, gateway, dan MQTT disimpan sebagai boolean; dihitung ulang tiap 5 detik
// (selaras interval heartbeat MQTT) agar perubahan ke offline/terputus hampir seketika.
Schedule::command('status:refresh')->everyFiveSeconds()->withoutOverlapping();
