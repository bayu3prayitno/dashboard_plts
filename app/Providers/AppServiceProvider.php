<?php

namespace App\Providers;

use App\Models\Alert;
use App\Models\Device;
use App\Models\Setting;
use App\Observers\SettingObserver;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Http\Controllers\DashboardController;

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
        Setting::observe(SettingObserver::class);

        // Data untuk header & sidebar
        View::composer('layouts.app', function ($view) {
            // Hitung di database (1 query), bukan memuat semua alert terbuka ke memori
            $counts = Alert::query()->open()
                ->selectRaw('COUNT(*) AS open_count, COALESCE(SUM(status = ?), 0) AS fresh_count', [Alert::STATUS_NEW])
                ->first();

            $view->with([
                // Halaman ber-layout ini selalu di balik middleware auth
                'layoutUser' => [
                    'nama' => auth()->user()?->name ?? 'Tamu',
                    'role' => auth()->user()?->roleLabel() ?? '',
                    'super' => (bool) auth()->user()?->isSuperAdmin(),
                ],
                'layoutTimezone' => DashboardController::tz(),
                'layoutActiveAlerts' => (int) $counts->open_count,
                'layoutFreshCount' => (int) $counts->fresh_count,
                // Panel notifikasi hanya menampilkan 5 teratas
                'layoutFreshAlerts' => $counts->fresh_count == 0 ? [] : Alert::query()
                    ->where('status', Alert::STATUS_NEW)
                    ->with('device:id,name')
                    ->latest('triggered_at')
                    ->limit(5)
                    ->get()
                    ->map(fn (Alert $a) => [
                        'category' => $a->typeLabel(),
                        'device' => $a->device?->name ?? '-',
                        'text' => $a->message(),
                        'waktu' => $a->triggered_at,
                    ])
                    ->values()
                    ->all(),
                'layoutDevices' => Device::query()
                    ->orderBy('node_id')
                    ->get(['id', 'node_id', 'name', 'location'])
                    ->map(fn (Device $d) => [
                        'id' => $d->id,
                        'nama' => $d->name,
                        'lokasi' => $d->location,
                        'node_id' => $d->node_id,
                        'url' => route('devices.show', $d->id),
                    ])
                    ->values()
                    ->all(),
            ]);
        });
    }
}
