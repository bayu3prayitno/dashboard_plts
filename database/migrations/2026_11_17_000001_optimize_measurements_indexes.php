<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            try {
                DB::statement('DROP INDEX measurements_device_id_recorded_at_index ON measurements');
            } catch (\Exception $e) {
                // Index tidak ada, lanjut
            }

            DB::statement('ALTER TABLE measurements ADD INDEX idx_device_recorded (device_id, recorded_at DESC)');
            DB::statement('ALTER TABLE measurements ADD INDEX idx_recorded_device (recorded_at DESC, device_id)');
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            try {
                DB::statement('DROP INDEX idx_device_recorded ON measurements');
            } catch (\Exception $e) {
                // Index tidak ada
            }

            try {
                DB::statement('DROP INDEX idx_recorded_device ON measurements');
            } catch (\Exception $e) {
                // Index tidak ada
            }

            DB::statement('ALTER TABLE measurements ADD INDEX measurements_device_id_recorded_at_index (device_id, recorded_at)');
        }
    }
};
