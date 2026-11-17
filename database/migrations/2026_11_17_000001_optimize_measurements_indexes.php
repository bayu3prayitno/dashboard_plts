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
            DB::statement('ALTER TABLE measurements DROP INDEX IF EXISTS measurements_device_id_recorded_at_index');
            DB::statement('ALTER TABLE measurements ADD INDEX idx_device_recorded (device_id, recorded_at DESC)');
            DB::statement('ALTER TABLE measurements ADD INDEX idx_recorded_device (recorded_at DESC, device_id)');
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE measurements DROP INDEX IF EXISTS idx_device_recorded');
            DB::statement('ALTER TABLE measurements DROP INDEX IF EXISTS idx_recorded_device');
            DB::statement('ALTER TABLE measurements ADD INDEX measurements_device_id_recorded_at_index (device_id, recorded_at)');
        }
    }
};
