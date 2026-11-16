<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Gateway;
use App\Models\Measurement;
use App\Services\TelemetryIngestor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelemetryOverwriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_packet_overwrites_existing_measurement(): void
    {
        $gateway = Gateway::create([
            'name' => 'Gateway Test', 'status' => 'offline', 'mqtt_status' => 'disconnected', 'internet_status' => 'disconnected',
        ]);
        $device = Device::create([
            'gateway_id' => $gateway->id, 'node_id' => 1, 'name' => 'Node 01', 'location' => 'A', 'is_active' => true,
        ]);

        $payload = fn (float $temp) => [
            'node_id' => 1, 'packet_no' => 1009, 'temperature' => $temp, 'voltage' => 220, 'current' => 1.2,
            'power' => 14, 'irradiance' => 600, 'rssi' => -70, 'snr' => 8, 'packet_loss' => 0,
        ];

        $ingestor = app(TelemetryIngestor::class);
        $ingestor->ingest($gateway->gateway_code, $payload(28.5));
        $ingestor->ingest($gateway->gateway_code, $payload(31.0));

        $this->assertSame(1, Measurement::where('device_id', $device->id)->where('packet_no', 1009)->count());
        $this->assertSame(31.0, (float) Measurement::where('device_id', $device->id)->where('packet_no', 1009)->value('temperature'));
        $this->assertTrue($device->fresh()->status() === Device::STATUS_ONLINE);
    }
}
