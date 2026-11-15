<?php

namespace App\Providers;

use App\Http\Controllers\DashboardController;
use App\Models\Alert;
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
            $openAlerts = Alert::query()->open()->with('device:id,name')->latest('triggered_at')->get();

            $view->with([
                // Halaman ber-layout ini selalu di balik middleware auth
                'layoutUser' => [
                    'nama' => auth()->user()?->name ?? 'Tamu',
                    'role' => auth()->user()?->roleLabel() ?? '',
                    'super' => (bool) auth()->user()?->isSuperAdmin(),
                ],
                'layoutActiveAlerts' => $openAlerts->count(),
                'layoutFreshAlerts' => $openAlerts
                    ->where('status', Alert::STATUS_NEW)
                    ->map(fn (Alert $a) => [
                        'device' => $a->device?->name ?? '-',
                        'text' => $a->message(),
                        'waktu' => $a->triggered_at,
                    ])
                    ->values()
                    ->all(),
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
