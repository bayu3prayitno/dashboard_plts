<?php

namespace Tests\Feature;

use App\Models\Gateway;
use App\Services\GatewayStatusUpdater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GatewayStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_internet_status_follows_gateway_message(): void
    {
        $gateway = Gateway::create([
            'name' => 'Gateway Test', 'status' => 'offline', 'mqtt_status' => 'disconnected', 'internet_status' => 'disconnected',
        ]);
        $updater = app(GatewayStatusUpdater::class);

        $this->assertTrue($updater->update($gateway->gateway_code, ['internet' => true]));
        $this->assertSame('connected', $gateway->fresh()->internet_status);

        $this->assertTrue($updater->update($gateway->gateway_code, ['internet' => false]));
        $this->assertSame('disconnected', $gateway->fresh()->internet_status);
    }

    public function test_invalid_or_unknown_status_is_ignored(): void
    {
        $gateway = Gateway::create([
            'name' => 'Gateway Test', 'status' => 'offline', 'mqtt_status' => 'disconnected', 'internet_status' => 'disconnected',
        ]);
        $updater = app(GatewayStatusUpdater::class);

        $this->assertFalse($updater->update($gateway->gateway_code, ['foo' => 1]));
        $this->assertFalse($updater->update($gateway->gateway_code, ['internet' => 'mungkin']));
        $this->assertFalse($updater->update('GW-999', ['internet' => true]));
        $this->assertSame('disconnected', $gateway->fresh()->internet_status);
    }
}
