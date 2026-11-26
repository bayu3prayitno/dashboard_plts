<?php

namespace Tests\Feature;

use App\Models\Gateway;
use App\Models\Setting;
use App\Services\GatewayStatusUpdater;
use App\Support\SettingsCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class GatewayStatusTest extends TestCase
{
    use RefreshDatabase;

    private function makeGateway(): Gateway
    {
        return Gateway::create([
            'name' => 'Gateway Test', 'status' => 'offline', 'mqtt_status' => 'disconnected', 'internet_status' => 'disconnected',
        ]);
    }

    public function test_message_from_gateway_marks_internet_connected(): void
    {
        $gateway = $this->makeGateway();

        $this->assertTrue(app(GatewayStatusUpdater::class)->markInternetConnected($gateway->gateway_code));

        $fresh = $gateway->fresh();
        $this->assertSame('connected', $fresh->internet_status);
        $this->assertNotNull($fresh->internet_last_seen_at);
    }

    public function test_unknown_gateway_is_not_updated(): void
    {
        $gateway = $this->makeGateway();

        $this->assertFalse(app(GatewayStatusUpdater::class)->markInternetConnected('GW-999'));
        $this->assertSame('disconnected', $gateway->fresh()->internet_status);
    }

    public function test_internet_stays_connected_within_timeout(): void
    {
        $gateway = $this->makeGateway();
        app(GatewayStatusUpdater::class)->markInternetConnected($gateway->gateway_code);
        $gateway->update(['internet_last_seen_at' => now()->subMinutes(Gateway::connectionTimeoutMinutes() - 1)]);

        $this->assertSame('connected', $gateway->fresh()->internet_status);
    }

    public function test_internet_is_disconnected_after_timeout_without_messages(): void
    {
        $gateway = $this->makeGateway();
        app(GatewayStatusUpdater::class)->markInternetConnected($gateway->gateway_code);
        $gateway->update(['internet_last_seen_at' => now()->subMinutes(Gateway::connectionTimeoutMinutes() + 1)]);

        // Tampilan langsung benar walau perintah terjadwal belum berjalan
        $this->assertSame('disconnected', $gateway->fresh()->internet_status);
        $this->assertSame('connected', $gateway->fresh()->getRawOriginal('internet_status'));

        Artisan::call('gateways:check-internet');

        $this->assertSame('disconnected', $gateway->fresh()->getRawOriginal('internet_status'));
    }

    public function test_connection_timeout_follows_offline_timeout_setting(): void
    {
        Setting::updateOrCreate(['key' => 'offline_timeout_minutes'], ['value' => '30', 'description' => 'Durasi (menit) sebelum perangkat dianggap offline']);
        SettingsCache::forget('offline_timeout_minutes');

        $gateway = $this->makeGateway();
        app(GatewayStatusUpdater::class)->markInternetConnected($gateway->gateway_code);

        // 20 menit tanpa paket: masih terhubung karena Offline Timeout 30 menit
        $gateway->update(['internet_last_seen_at' => now()->subMinutes(20)]);
        $this->assertSame('connected', $gateway->fresh()->internet_status);

        $gateway->update(['internet_last_seen_at' => now()->subMinutes(31)]);
        $this->assertSame('disconnected', $gateway->fresh()->internet_status);
    }

    public function test_new_message_after_timeout_reconnects_internet(): void
    {
        $gateway = $this->makeGateway();
        app(GatewayStatusUpdater::class)->markInternetConnected($gateway->gateway_code);
        $gateway->update(['internet_last_seen_at' => now()->subHour()]);
        Artisan::call('gateways:check-internet');

        app(GatewayStatusUpdater::class)->markInternetConnected($gateway->gateway_code);

        $this->assertSame('connected', $gateway->fresh()->internet_status);
    }
}
