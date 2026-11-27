<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Status koneksi disimpan sebagai boolean (logika sama seperti saat masih dihitung di tampilan):
 * - devices.is_online          : node mengirim data dalam Offline Timeout (dari last_seen_at)
 * - gateways.is_online         : gateway mengirim paket data valid dalam Offline Timeout (dari last_seen_at)
 * - gateways.is_connected      : ada pesan apa pun dari gateway dalam Offline Timeout (dari internet_last_seen_at)
 * - gateways.is_mqtt_connected : heartbeat subscriber MQTT tidak lebih tua dari 30 detik
 * Diset saat data/pesan masuk, dan dihitung ulang tiap 5 detik oleh `status:refresh`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->boolean('is_online')->default(false)->after('is_active');
        });

        Schema::table('gateways', function (Blueprint $table) {
            $table->boolean('is_online')->default(false)->after('name');
            $table->boolean('is_connected')->default(false)->after('is_online');
            $table->boolean('is_mqtt_connected')->default(false)->after('is_connected');
        });

        // Nilai awal dari data yang sudah ada
        $minutes = (int) (DB::table('settings')->where('key', 'offline_timeout_minutes')->value('value') ?: 15);
        $cutoff = now()->subMinutes($minutes);

        DB::table('devices')->where('last_seen_at', '>=', $cutoff)->update(['is_online' => true]);
        DB::table('gateways')->where('last_seen_at', '>=', $cutoff)->update(['is_online' => true]);
        DB::table('gateways')->where('internet_last_seen_at', '>=', $cutoff)->update(['is_connected' => true]);
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('is_online');
        });

        Schema::table('gateways', function (Blueprint $table) {
            $table->dropColumn(['is_online', 'is_connected', 'is_mqtt_connected']);
        });
    }
};
