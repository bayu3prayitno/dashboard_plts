<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paket yang sebagian field-nya hilang tetap diterima; field yang kosong disimpan sebagai null.
 * Hanya node_id dan packet_no yang wajib.
 */
return new class extends Migration
{
    private const DECIMAL_COLUMNS = ['temperature', 'voltage', 'current', 'power', 'irradiance', 'snr', 'packet_loss'];

    public function up(): void
    {
        Schema::table('measurements', function (Blueprint $table) {
            foreach (self::DECIMAL_COLUMNS as $column) {
                $table->decimal($column, 8, 2)->nullable()->change();
            }
            $table->integer('rssi')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Baris dengan nilai null harus diisi dulu sebelum kolom kembali wajib
        Schema::table('measurements', function (Blueprint $table) {
            foreach (self::DECIMAL_COLUMNS as $column) {
                $table->decimal($column, 8, 2)->nullable(false)->change();
            }
            $table->integer('rssi')->nullable(false)->change();
        });
    }
};
