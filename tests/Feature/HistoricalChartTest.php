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
            'name' => 'Gateway Test',
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

        $this->assertSame('latest', $json['meta']['mode']);
        $this->assertCount(5, $json['datasets']); // 5 sensor x 1 node
        $temperature = collect($json['datasets'])->firstWhere('sensor', 'temperature');
        $this->assertSame('Node 01', $temperature['label']);
        $this->assertEquals([31.0, 30.0], array_column($temperature['data'], 'y'));

        $this->actingAs($user)->get(route('historical.index'))
            ->assertOk()
            ->assertSee('chart-temperature', false)
            ->assertSee('chart-irradiance', false)
            ->assertSee('data-vc-sensor="current"', false);
    }
}
