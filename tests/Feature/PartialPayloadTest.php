<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Device;
use App\Models\Gateway;
use App\Models\Measurement;
use App\Models\Threshold;
use App\Services\TelemetryIngestor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartialPayloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_packet_is_stored_with_null_fields_and_brings_device_online(): void
    {
        $gateway = Gateway::create([
            'name' => 'Gateway Test', 'status' => 'offline', 'mqtt_status' => 'disconnected', 'internet_status' => 'disconnected',
        ]);
        $device = Device::create([
            'gateway_id' => $gateway->id, 'node_id' => 1, 'name' => 'Node 01', 'location' => 'A', 'is_active' => true,
        ]);
        // Ambang suhu ketat: nilai null tidak boleh dianggap 0 lalu memicu alert
        Threshold::create(['device_id' => null, 'sensor_type' => 'temperature', 'min_value' => 10, 'max_value' => 40, 'is_active' => true]);

        app(TelemetryIngestor::class)->ingest($gateway->gateway_code, [
            'node_id' => 1,
            'packet_no' => 2001,
        ]);

        $m = Measurement::where('device_id', $device->id)->where('packet_no', 2001)->first();
        $this->assertNotNull($m);
        $this->assertNull($m->temperature);
        $this->assertNull($m->packet_loss);
        $this->assertSame(Device::STATUS_ONLINE, $device->fresh()->status());
        $this->assertSame(0, Alert::count());
    }
}
