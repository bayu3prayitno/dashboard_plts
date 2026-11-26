<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Gateway;
use App\Models\Measurement;
use App\Services\LegacyGatewayPayloadAdapter;
use App\Services\TelemetryIngestor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyGatewayPayloadAdapterTest extends TestCase
{
    use RefreshDatabase;

    private Gateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = Gateway::create([
            'name' => 'Gateway Test', 'status' => 'offline', 'mqtt_status' => 'disconnected', 'internet_status' => 'disconnected',
        ]);
        Device::create([
            'gateway_id' => $this->gateway->id, 'node_id' => 1, 'name' => 'Node 01', 'location' => 'A', 'is_active' => true,
        ]);
    }

    /** @return array<string, mixed> */
    private function firmwarePayload(array $overrides = []): array
    {
        return array_merge([
            'temp' => 31.5, 'voltage' => 12.3, 'current' => 1.2, 'power' => 14.8,
            'irradiance' => 650.0, 'rssi' => -95, 'snr' => 7.5,
        ], $overrides);
    }

    public function test_node_name_maps_to_node_id(): void
    {
        $this->assertSame(1, LegacyGatewayPayloadAdapter::resolveNodeId('node1'));
        $this->assertSame(3, LegacyGatewayPayloadAdapter::resolveNodeId('node3'));
        $this->assertSame(12, LegacyGatewayPayloadAdapter::resolveNodeId('node12'));
    }

    public function test_other_node_name_formats_are_rejected(): void
    {
        $this->assertNull(LegacyGatewayPayloadAdapter::resolveNodeId('5'));
        $this->assertNull(LegacyGatewayPayloadAdapter::resolveNodeId('node0'));
        $this->assertNull(LegacyGatewayPayloadAdapter::resolveNodeId('node01'));
        $this->assertNull(LegacyGatewayPayloadAdapter::resolveNodeId('sensor-x'));
    }

    public function test_firmware_payload_is_translated_to_ingest_format(): void
    {
        $data = app(LegacyGatewayPayloadAdapter::class)->translate($this->gateway->gateway_code, 'node1', $this->firmwarePayload());

        $this->assertSame(1, $data['node_id']);
        $this->assertSame(1, $data['packet_no']);
        $this->assertSame(31.5, $data['temperature']);
        $this->assertSame(-95, $data['rssi']);
        $this->assertNull($data['packet_loss']);
        $this->assertNull($data['recorded_at']);
    }

    public function test_packet_number_continues_from_last_stored_packet(): void
    {
        $device = Device::where('node_id', 1)->first();
        Measurement::create(['device_id' => $device->id, 'packet_no' => 7, 'recorded_at' => now()]);

        $data = app(LegacyGatewayPayloadAdapter::class)->translate($this->gateway->gateway_code, 'node1', $this->firmwarePayload());

        $this->assertSame(8, $data['packet_no']);
    }

    public function test_non_finite_or_missing_values_become_null(): void
    {
        $data = app(LegacyGatewayPayloadAdapter::class)->translate(
            $this->gateway->gateway_code, 'node1', ['temp' => 'nan', 'voltage' => 'abc'],
        );

        $this->assertNull($data['temperature']);
        $this->assertNull($data['voltage']);
        $this->assertNull($data['current']);
        $this->assertNull($data['rssi']);
    }

    public function test_unknown_node_is_not_translated(): void
    {
        $this->assertNull(app(LegacyGatewayPayloadAdapter::class)->translate($this->gateway->gateway_code, 'node9', $this->firmwarePayload()));
        $this->assertNull(app(LegacyGatewayPayloadAdapter::class)->translate('GW-TIDAK-ADA', 'node1', $this->firmwarePayload()));
    }

    public function test_translated_firmware_packet_is_stored_end_to_end(): void
    {
        $adapter = app(LegacyGatewayPayloadAdapter::class);
        $ingestor = app(TelemetryIngestor::class);

        $ingestor->ingest($this->gateway->gateway_code, $adapter->translate($this->gateway->gateway_code, 'node1', $this->firmwarePayload()));
        $ingestor->ingest($this->gateway->gateway_code, $adapter->translate($this->gateway->gateway_code, 'node1', $this->firmwarePayload(['temp' => 33.0])));

        $device = Device::where('node_id', 1)->first();
        $this->assertSame(2, Measurement::where('device_id', $device->id)->count());
        $this->assertSame(2, (int) Measurement::where('device_id', $device->id)->max('packet_no'));
        $this->assertSame(33.0, (float) Measurement::where('device_id', $device->id)->orderByDesc('packet_no')->first()->temperature);
    }
}
