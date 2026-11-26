<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Gateway;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceNodeIdTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    }

    private function makeGateway(string $name): Gateway
    {
        return Gateway::create([
            'name' => $name, 'status' => 'offline', 'mqtt_status' => 'disconnected', 'internet_status' => 'disconnected',
        ]);
    }

    private function makeDevice(Gateway $gateway, int $nodeId): Device
    {
        return Device::create([
            'gateway_id' => $gateway->id, 'node_id' => $nodeId, 'name' => "Node {$nodeId}", 'location' => 'A', 'is_active' => true,
        ]);
    }

    public function test_node_id_cannot_repeat_within_the_same_gateway(): void
    {
        $gateway = $this->makeGateway('Gateway A');
        $this->makeDevice($gateway, 1);

        $response = $this->actingAs($this->user)->post(route('devices.store'), [
            'gateway_id' => $gateway->id, 'node_id' => 1, 'name' => 'Node Baru', 'location' => 'B',
        ]);

        $response->assertSessionHasErrors('node_id');
        $this->assertSame(1, Device::where('gateway_id', $gateway->id)->where('node_id', 1)->count());
    }

    public function test_node_id_can_repeat_on_a_different_gateway(): void
    {
        $gatewayA = $this->makeGateway('Gateway A');
        $gatewayB = $this->makeGateway('Gateway B');
        $this->makeDevice($gatewayA, 1);

        $response = $this->actingAs($this->user)->post(route('devices.store'), [
            'gateway_id' => $gatewayB->id, 'node_id' => 1, 'name' => 'Node 1 B', 'location' => 'B',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(2, Device::where('node_id', 1)->count());
    }

    public function test_moving_device_to_gateway_that_already_uses_its_node_id_is_rejected(): void
    {
        $gatewayA = $this->makeGateway('Gateway A');
        $gatewayB = $this->makeGateway('Gateway B');
        $this->makeDevice($gatewayA, 1);
        $device = $this->makeDevice($gatewayB, 1);

        $response = $this->actingAs($this->user)->put(route('devices.update', $device), [
            'gateway_id' => $gatewayA->id, 'node_id' => 1, 'name' => 'Node 1', 'location' => 'A', 'status' => 'active',
        ]);

        $response->assertSessionHasErrors('node_id');
        $this->assertSame($gatewayB->id, $device->fresh()->gateway_id);
    }

    public function test_updating_device_without_changing_gateway_does_not_conflict_with_itself(): void
    {
        $gateway = $this->makeGateway('Gateway A');
        $device = $this->makeDevice($gateway, 1);

        $response = $this->actingAs($this->user)->put(route('devices.update', $device), [
            'gateway_id' => $gateway->id, 'node_id' => 1, 'name' => 'Nama Baru', 'location' => 'A', 'status' => 'active',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('Nama Baru', $device->fresh()->name);
    }

    public function test_database_rejects_duplicate_node_id_within_the_same_gateway(): void
    {
        $gateway = $this->makeGateway('Gateway A');
        $this->makeDevice($gateway, 1);

        $this->expectException(UniqueConstraintViolationException::class);
        $this->makeDevice($gateway, 1);
    }

    public function test_create_form_suggests_next_node_id_per_gateway(): void
    {
        $gatewayA = $this->makeGateway('Gateway A');
        $gatewayB = $this->makeGateway('Gateway B');
        $this->makeDevice($gatewayA, 1);
        $this->makeDevice($gatewayA, 3);

        $response = $this->actingAs($this->user)->get(route('devices.create'));

        $response->assertOk();
        $response->assertViewHas('nextNodeIds', fn ($ids) => $ids[$gatewayA->id] === 4 && $ids[$gatewayB->id] === 1);
    }
}
