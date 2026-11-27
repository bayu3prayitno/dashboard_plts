<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Device;
use App\Models\Gateway;
use App\Models\Measurement;
use App\Models\Threshold;
use App\Services\TelemetryIngestor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PartialPayloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_packet_is_stored_with_null_fields_and_brings_device_online(): void
    {
        $gateway = Gateway::create([
            'name' => 'Gateway Test',
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

    public function test_packet_gap_is_logged(): void
    {
        $gateway = Gateway::create([
            'name' => 'Gateway Test',
        ]);
        Device::create([
            'gateway_id' => $gateway->id, 'node_id' => 1, 'name' => 'Node 01', 'location' => 'A', 'is_active' => true,
        ]);

        Log::spy();
        $ingestor = app(TelemetryIngestor::class);
        $ingestor->ingest($gateway->gateway_code, ['node_id' => 1, 'packet_no' => 1]);
        $ingestor->ingest($gateway->gateway_code, ['node_id' => 1, 'packet_no' => 4]);

        Log::shouldHaveReceived('warning')
            ->with('MQTT: gap paket terdeteksi', \Mockery::on(function (array $context): bool {
                return $context['packet_sebelumnya'] === 1
                    && $context['packet_saat_ini'] === 4
                    && $context['paket_hilang_tersirat'] === 2;
            }))
            ->once();
    }
}
