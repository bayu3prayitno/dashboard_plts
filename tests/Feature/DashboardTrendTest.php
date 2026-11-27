<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Gateway;
use App\Models\Measurement;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTrendTest extends TestCase
{
    use RefreshDatabase;

    public function test_trend_has_one_series_per_active_device(): void
    {
        // Query tren memakai DATE_FORMAT/DATE_ADD (MySQL). Di SQLite test dilewati; jalankan dengan koneksi MySQL.
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Query tren dashboard khusus MySQL.');
        }

        $user = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $gateway = Gateway::create([
            'name' => 'Gateway Test',
        ]);
        $a = Device::create(['gateway_id' => $gateway->id, 'node_id' => 1, 'name' => 'Node A', 'location' => 'X', 'is_active' => true]);
        $b = Device::create(['gateway_id' => $gateway->id, 'node_id' => 2, 'name' => 'Node B', 'location' => 'X', 'is_active' => true]);
        Device::create(['gateway_id' => $gateway->id, 'node_id' => 3, 'name' => 'Node C', 'location' => 'X', 'is_active' => false]);

        $now = CarbonImmutable::now();
        // Node A 30 dan Node B 60 pada waktu yang sama: tetap dua garis terpisah, tidak dirata-rata
        Measurement::create(['device_id' => $a->id, 'packet_no' => 1, 'temperature' => 30, 'voltage' => 220, 'current' => 1, 'power' => 10, 'irradiance' => 500, 'rssi' => -70, 'snr' => 8, 'packet_loss' => 0, 'recorded_at' => $now->subMinutes(5)]);
        Measurement::create(['device_id' => $b->id, 'packet_no' => 1, 'temperature' => 60, 'voltage' => 220, 'current' => 1, 'power' => 20, 'irradiance' => 500, 'rssi' => -70, 'snr' => 8, 'packet_loss' => 0, 'recorded_at' => $now->subMinutes(5)]);

        $response = $this->actingAs($user)->get(route('dashboard.index'));
        $response->assertOk();
        $response->assertViewHas('trendDevices', function (array $trend) {
            $series = $trend['temperature']['24h']['series'];
            $names = array_column($series, 'name');

            return $names === ['Node A', 'Node B']
                && count($trend['temperature']['24h']['labels']) === 48
                && in_array(30.0, $series[0]['data'], true)
                && in_array(60.0, $series[1]['data'], true);
        });
    }
}
