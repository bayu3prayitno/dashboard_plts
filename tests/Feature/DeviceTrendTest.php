<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Gateway;
use App\Models\Measurement;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceTrendTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_page_renders_24h_trend_with_recent_measurements(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $gateway = Gateway::create([
            'name' => 'Gateway Test', 'status' => 'online', 'mqtt_status' => 'connected', 'internet_status' => 'connected',
        ]);
        $device = Device::create([
            'gateway_id' => $gateway->id, 'node_id' => 1, 'name' => 'Node 01', 'location' => 'A', 'is_active' => true,
        ]);

        $now = CarbonImmutable::now();
        foreach ([5, 40, 200] as $minutesAgo) {
            Measurement::create([
                'device_id' => $device->id, 'packet_no' => $minutesAgo,
                'temperature' => 30, 'voltage' => 220, 'current' => 1.5, 'power' => 10,
                'irradiance' => 500, 'rssi' => -70, 'snr' => 8, 'packet_loss' => 0,
                'recorded_at' => $now->subMinutes($minutesAgo),
            ]);
        }

        $response = $this->actingAs($user)->get(route('devices.show', $device));

        $response->assertOk();
        $response->assertSee('chart-temperature', false);
        $response->assertSee('chart-voltage-current', false);
        $response->assertViewHas('trend24', function (array $trend) {
            return count($trend['labels']) === 48
                && count($trend['temperature']) === 48
                && count(array_filter($trend['temperature'], fn ($v) => $v !== null)) === 3 // 3 paket di 3 bucket berbeda
                && array_values(array_unique(array_filter($trend['temperature'], fn ($v) => $v !== null))) === [30.0];
        });
    }
}
