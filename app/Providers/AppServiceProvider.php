<?php

namespace App\Providers;

use App\Models\Alert;
use App\Models\Device;
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
        // Data untuk header & sidebar
        View::composer('layouts.app', function ($view) {
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
