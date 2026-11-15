<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Ambang batas standar sistem (device_id NULL), sama dengan nilai di halaman konfigurasi. */
    private const DEFAULTS = [
        'temperature' => [15.0, 35.0],
        'voltage' => [190.0, 240.0],
        'current' => [0.0, 10.0],
        'power' => [0.0, 2500.0],
        'irradiance' => [0.0, 1200.0],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        DB::table('thresholds')->insert(array_map(fn (string $sensor, array $range) => [
            'device_id' => null,
            'sensor_type' => $sensor,
            'min_value' => $range[0],
            'max_value' => $range[1],
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ], array_keys(self::DEFAULTS), self::DEFAULTS));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('thresholds')->whereNull('device_id')->delete();
    }
};
