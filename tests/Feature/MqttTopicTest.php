<?php

namespace Tests\Feature;

use App\Console\Commands\MqttSubscribe;
use App\Models\Device;
use App\Models\Gateway;
use App\Models\Measurement;
use App\Services\GatewayStatusUpdater;
use App\Services\LegacyGatewayPayloadAdapter;
use App\Services\TelemetryIngestor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class MqttTopicTest extends TestCase
{
    use RefreshDatabase;

    private function makeGateway(string $name): Gateway
    {
        return Gateway::create([
            'name' => $name, 'status' => 'offline', 'mqtt_status' => 'disconnected', 'internet_status' => 'disconnected',
        ]);
    }

    private function makeDevice(Gateway $gateway, int $nodeId): Device
    {
        return Device::create([
            'gateway_id' => $gateway->id, 'node_id' => $nodeId, 'name' => "Node {$nodeId} {$gateway->name}", 'location' => 'A', 'is_active' => true,
        ]);
    }

    /** Kirim pesan ke subscriber seolah-olah diterima dari broker. */
    private function receive(string $topic, array $payload): void
    {
        $command = app(MqttSubscribe::class);

        (new ReflectionMethod($command, 'handleMessage'))->invoke(
            $command, $topic, json_encode($payload),
            app(TelemetryIngestor::class), app(LegacyGatewayPayloadAdapter::class), app(GatewayStatusUpdater::class),
        );
    }

    public function test_device_topic_uses_gateway_code_and_node_id(): void
    {
        $gateway = $this->makeGateway('Gateway A');
        $device = $this->makeDevice($gateway, 4);

        $this->assertSame("vpp/kampus/{$gateway->gateway_code}/node4", $device->mqttTopic());

        // Topik yang ditampilkan dashboard diterima apa adanya oleh subscriber
        $this->receive($device->mqttTopic(), ['temp' => 30.5]);
        $this->assertSame(1, Measurement::where('device_id', $device->id)->count());
    }

    public function test_message_is_stored_on_the_gateway_named_in_the_topic(): void
    {
        $gatewayA = $this->makeGateway('Gateway A');
        $gatewayB = $this->makeGateway('Gateway B');
        $deviceA = $this->makeDevice($gatewayA, 1);
        $deviceB = $this->makeDevice($gatewayB, 1);

        $this->receive($deviceB->mqttTopic(), ['temp' => 30.5, 'voltage' => 220.0]);

        $this->assertSame(0, Measurement::where('device_id', $deviceA->id)->count());
        $this->assertSame(1, Measurement::where('device_id', $deviceB->id)->count());
        $this->assertSame('connected', $gatewayB->fresh()->internet_status);
        $this->assertSame('disconnected', $gatewayA->fresh()->internet_status);
    }

    public function test_message_with_unknown_gateway_or_old_topic_is_ignored(): void
    {
        $gateway = $this->makeGateway('Gateway A');
        $this->makeDevice($gateway, 1);

        $this->receive('vpp/kampus/GW-TIDAK-ADA/node1', ['temp' => 30.5]);
        $this->receive('vpp/kampus/node1', ['temp' => 30.5]);

        $this->assertSame(0, Measurement::count());
        $this->assertSame('disconnected', $gateway->fresh()->internet_status);
    }
}
