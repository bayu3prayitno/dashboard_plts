<?php

namespace App\Providers;

use App\Http\Controllers\DashboardController;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Data untuk header & sidebar (badge peringatan, notifikasi, pencarian perangkat).
        // Masih tiruan — ganti dengan query model saat backend siap.
        View::composer('layouts.app', function ($view) {
            $now = CarbonImmutable::now(DashboardController::TZ);
            $alerts = DashboardController::mockAlerts($now);

            $view->with([
                // Halaman ber-layout ini selalu di balik middleware auth
                'layoutUser' => [
                    'nama' => auth()->user()?->name ?? 'Tamu',
                    'role' => auth()->user()?->roleLabel() ?? '',
                    'super' => (bool) auth()->user()?->isSuperAdmin(),
                ],
                'layoutActiveAlerts' => count(array_filter($alerts, fn ($a) => $a['status'] !== 'selesai')),
                'layoutFreshAlerts' => array_values(array_filter($alerts, fn ($a) => $a['status'] === 'baru')),
                'layoutDevices' => array_map(fn ($d) => [
                    'id' => $d['id'],
                    'nama' => $d['nama'],
                    'lokasi' => $d['lokasi'],
                    'node_id' => $d['node_id'],
                    'url' => route('devices.show', $d['id']),
                ], DashboardController::mockDevices($now)),
            ]);
        });
    }
}
