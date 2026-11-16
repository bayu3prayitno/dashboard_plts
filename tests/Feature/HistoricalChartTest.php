<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Gateway;
use App\Models\Measurement;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoricalChartTest extends TestCase
{
    use RefreshDatabase;

    public function test_chart_data_returns_series_per_sensor_and_page_renders_charts(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $gateway = Gateway::create([
            'name' => 'Gateway Test', 'status' => 'online', 'mqtt_status' => 'connected', 'internet_status' => 'connected',
        ]);
        $device = Device::create([
            'gateway_id' => $gateway->id, 'node_id' => 1, 'name' => 'Node 01', 'location' => 'A', 'is_active' => true,
        ]);

        // Dua paket di bucket yang tidak berdampingan (garis harus tetap bisa dibuat)
        $now = CarbonImmutable::now();
        foreach ([10, 300] as $n => $minutesAgo) {
            Measurement::create([
                'device_id' => $device->id, 'packet_no' => $n + 1,
                'temperature' => 30 + $n, 'voltage' => 220, 'current' => 1.5, 'power' => 10,
                'irradiance' => 500, 'rssi' => -70, 'snr' => 8, 'packet_loss' => 0,
                'recorded_at' => $now->subMinutes($minutesAgo),
            ]);
        }

        $json = $this->actingAs($user)->getJson(route('historical.chartData'))->assertOk()->json();

        $this->assertCount(2, $json['labels']);
        $this->assertCount(5, $json['datasets']);
        $temperature = collect($json['datasets'])->firstWhere('sensor', 'temperature');
        $this->assertEquals([31.0, 30.0], $temperature['data']);

        $this->actingAs($user)->get(route('historical.index'))
            ->assertOk()
            ->assertSee('chart-temperature', false)
            ->assertSee('chart-irradiance', false);
    }
}
