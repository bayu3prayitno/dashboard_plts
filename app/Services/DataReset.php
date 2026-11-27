<?php

namespace App\Services;

use App\Http\Controllers\DashboardController;
use App\Models\Alert;
use App\Models\Gateway;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Tombol darurat developer: kosongkan data operasional tanpa menyentuh konfigurasi.
 * Pengguna, gateway, perangkat, ambang batas, dan pengaturan TIDAK pernah dihapus.
 */
class DataReset
{
    /** Kelompok data yang bisa dikosongkan => label tampilan. */
    public const SCOPES = [
        'measurements' => 'Data sensor (semua pembacaan node) + status terakhir gateway & node',
        'alerts' => 'Peringatan (alert)',
        'activity_logs' => 'Audit log',
    ];

    /**
     * @param  list<string>  $scopes  kunci dari SCOPES
     * @return array<string, int> jumlah baris yang dihapus per kelompok
     */
    public function run(array $scopes): array
    {
        $deleted = [];

        DB::transaction(function () use ($scopes, &$deleted) {
            if (in_array('alerts', $scopes, true)) {
                $deleted['alerts'] = DB::table('alerts')->delete();
            }

            if (in_array('measurements', $scopes, true)) {
                $deleted['measurements'] = DB::table('measurements')->delete();

                // Tanpa data, "terakhir terlihat" lama membuat node/gateway tampil online palsu
                DB::table('devices')->update(['last_seen_at' => null]);
                DB::table('gateways')->update([
                    'last_seen_at' => null,
                    'internet_last_seen_at' => null,
                ]);
            }

            if (in_array('activity_logs', $scopes, true)) {
                $deleted['activity_logs'] = DB::table('activity_logs')->delete();
            }
        });

        $this->forgetDashboardCache();

        return $deleted;
    }

    /** Dashboard menyimpan tren & alert di cache; buang agar data lama tidak tampil lagi. */
    private function forgetDashboardCache(): void
    {
        $hour = now(DashboardController::tz())->format('Y-m-d-H');
        $ids = Gateway::query()->pluck('id')->push(0);

        foreach ($ids as $id) {
            Cache::forget("dashboard_trend_{$id}_{$hour}");
            Cache::forget("dashboard_alerts_live_{$id}");
        }

        Alert::bumpVersion(); // browser yang terbuka ikut memuat ulang daftar alert
    }
}
