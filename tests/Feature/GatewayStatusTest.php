<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Gateway;
use App\Models\Setting;
use App\Services\GatewayStatusUpdater;
use App\Support\SettingsCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class GatewayStatusTest extends TestCase
{
    use RefreshDatabase;

    private function makeGateway(): Gateway
    {
        return Gateway::create([
            'name' => 'Gateway Test',
        ]);
    }

    public function test_new_gateway_starts_disconnected(): void
    {
        $gateway = $this->makeGateway()->fresh();

        $this->assertFalse($gateway->is_connected);
        $this->assertFalse($gateway->is_mqtt_connected);
        $this->assertSame('offline', $gateway->status);
        $this->assertSame('disconnected', $gateway->internet_status);
        $this->assertSame('disconnected', $gateway->mqtt_status);
    }

    public function test_new_gateway_follows_subscriber_mqtt_status_immediately(): void
    {
        Cache::put(Gateway::MQTT_HEARTBEAT_KEY, time(), 60);

        $gateway = $this->makeGateway()->fresh();

        $this->assertTrue($gateway->is_mqtt_connected);
        $this->assertSame('connected', $gateway->mqtt_status);
    }

    public function test_message_from_gateway_marks_connected(): void
    {
        $gateway = $this->makeGateway();

        $this->assertTrue(app(GatewayStatusUpdater::class)->markInternetConnected($gateway->gateway_code));

        $fresh = $gateway->fresh();
        $this->assertTrue($fresh->is_connected);
        $this->assertSame('connected', $fresh->internet_status);
        $this->assertNotNull($fresh->internet_last_seen_at);
    }

    public function test_unknown_gateway_is_not_updated(): void
    {
        $gateway = $this->makeGateway();

        $this->assertFalse(app(GatewayStatusUpdater::class)->markInternetConnected('GW-999'));
        $this->assertFalse($gateway->fresh()->is_connected);
    }

    public function test_refresh_keeps_gateway_connected_within_timeout(): void
    {
        $gateway = $this->makeGateway();
        app(GatewayStatusUpdater::class)->markInternetConnected($gateway->gateway_code);
        $gateway->update(['internet_last_seen_at' => now()->subMinutes(Gateway::connectionTimeoutMinutes() - 1)]);

        Artisan::call('status:refresh');

        $this->assertTrue($gateway->fresh()->is_connected);
    }

    public function test_refresh_disconnects_gateway_after_timeout(): void
    {
        $gateway = $this->makeGateway();
        app(GatewayStatusUpdater::class)->markInternetConnected($gateway->gateway_code);
        $gateway->update(['internet_last_seen_at' => now()->subMinutes(Gateway::connectionTimeoutMinutes() + 1)]);

        // Disimpan di database: tetap true sampai status:refresh berjalan
        $this->assertTrue($gateway->fresh()->is_connected);

        Artisan::call('status:refresh');

        $this->assertFalse($gateway->fresh()->is_connected);
        $this->assertSame('disconnected', $gateway->fresh()->internet_status);
    }

    public function test_refresh_follows_offline_timeout_setting(): void
    {
        Setting::updateOrCreate(['key' => 'offline_timeout_minutes'], ['value' => '30', 'description' => 'Durasi (menit) sebelum perangkat dianggap offline']);
        SettingsCache::forget('offline_timeout_minutes');

        $gateway = $this->makeGateway();
        app(GatewayStatusUpdater::class)->markInternetConnected($gateway->gateway_code);

        // 20 menit tanpa paket: masih terhubung karena Offline Timeout 30 menit
        $gateway->update(['internet_last_seen_at' => now()->subMinutes(20)]);
        Artisan::call('status:refresh');
        $this->assertTrue($gateway->fresh()->is_connected);

        $gateway->update(['internet_last_seen_at' => now()->subMinutes(31)]);
        Artisan::call('status:refresh');
        $this->assertFalse($gateway->fresh()->is_connected);
    }

    public function test_new_message_after_timeout_reconnects_gateway(): void
    {
        $gateway = $this->makeGateway();
        app(GatewayStatusUpdater::class)->markInternetConnected($gateway->gateway_code);
        $gateway->update(['internet_last_seen_at' => now()->subHour()]);
        Artisan::call('status:refresh');
        $this->assertFalse($gateway->fresh()->is_connected);

        app(GatewayStatusUpdater::class)->markInternetConnected($gateway->gateway_code);

        $this->assertTrue($gateway->fresh()->is_connected);
    }

    public function test_refresh_sets_device_online_flag_from_last_seen(): void
    {
        $gateway = $this->makeGateway();
        $recent = Device::create(['gateway_id' => $gateway->id, 'node_id' => 1, 'name' => 'Node 1', 'location' => 'A', 'is_active' => true, 'is_online' => true, 'last_seen_at' => now()]);
        $stale = Device::create(['gateway_id' => $gateway->id, 'node_id' => 2, 'name' => 'Node 2', 'location' => 'A', 'is_active' => true, 'is_online' => true, 'last_seen_at' => now()->subHour()]);

        Artisan::call('status:refresh');

        $this->assertTrue($recent->fresh()->is_online);
        $this->assertSame(Device::STATUS_ONLINE, $recent->fresh()->status());
        $this->assertFalse($stale->fresh()->is_online);
        $this->assertSame(Device::STATUS_OFFLINE, $stale->fresh()->status());
    }

    public function test_gateway_online_follows_valid_packets_separately_from_connection(): void
    {
        // Pesan apa pun (mis. node belum terdaftar) = terhubung, tetapi gateway belum online
        $gateway = $this->makeGateway();
        app(GatewayStatusUpdater::class)->markInternetConnected($gateway->gateway_code);

        $fresh = $gateway->fresh();
        $this->assertTrue($fresh->is_connected);
        $this->assertFalse($fresh->is_online);
        $this->assertSame('offline', $fresh->status);

        // Paket data valid terakhir melewati timeout → offline lewat refresh
        $gateway->update(['is_online' => true, 'last_seen_at' => now()->subMinutes(Gateway::connectionTimeoutMinutes() + 1)]);
        Artisan::call('status:refresh');
        $this->assertFalse($gateway->fresh()->is_online);

        $gateway->update(['last_seen_at' => now()]);
        Artisan::call('status:refresh');
        $this->assertTrue($gateway->fresh()->is_online);
    }

    public function test_refresh_sets_mqtt_flag_from_subscriber_heartbeat(): void
    {
        $gateway = $this->makeGateway();

        Cache::put(Gateway::MQTT_HEARTBEAT_KEY, time(), 60);
        Artisan::call('status:refresh');
        $this->assertTrue($gateway->fresh()->is_mqtt_connected);
        $this->assertSame('connected', $gateway->fresh()->mqtt_status);

        // Subscriber mati: heartbeat basi → false
        Cache::put(Gateway::MQTT_HEARTBEAT_KEY, time() - Gateway::MQTT_HEARTBEAT_TIMEOUT - 1, 60);
        Artisan::call('status:refresh');
        $this->assertFalse($gateway->fresh()->is_mqtt_connected);
    }
}
