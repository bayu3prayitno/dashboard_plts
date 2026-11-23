<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Deteksi perangkat offline (PRD 4.4). Butuh scheduler: `php artisan schedule:work` atau cron `schedule:run`.
Schedule::command('alerts:check-offline')->everyMinute();

// Status internet gateway terputus bila status MQTT tidak diterima dalam 10 menit.
Schedule::command('gateways:check-internet')->everyMinute();
