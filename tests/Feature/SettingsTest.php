<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Device;
use App\Models\Gateway;
use App\Models\Setting;
use App\Models\User;
use App\Services\MqttSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'Bayu Tester', 'role' => User::ROLE_SUPER_ADMIN]);
    }

    /** @return array<string, mixed> */
    private function systemPayload(array $overrides = []): array
    {
        return array_merge([
            'timezone' => 'Asia/Jakarta',
            'data_retention_days' => 90,
            'offline_timeout_minutes' => 15,
        ], $overrides);
    }

    public function test_users_page_lists_real_users(): void
    {
        User::factory()->create(['name' => 'Operator Lapangan', 'role' => User::ROLE_SUPER_ADMIN, 'is_active' => false]);

        $this->actingAs($this->user)->get(route('settings.users'))
            ->assertOk()
            ->assertSee('Bayu Tester')
            ->assertSee('Operator Lapangan')
            ->assertSee('Super Admin')
            ->assertSee('Nonaktif');
    }

    public function test_system_page_shows_defaults_when_nothing_is_stored(): void
    {
        $this->actingAs($this->user)->get(route('settings.system'))
            ->assertOk()
            ->assertSee('Asia/Jakarta')
            ->assertSee('value="90"', false)
            ->assertSee('Broker MQTT')
            ->assertDontSee('mqtt_topic');
    }

    public function test_mqtt_topics_are_fixed_and_not_editable_from_menu(): void
    {
        $this->actingAs($this->user)
            ->post(route('settings.mqttUpdate'), $this->mqttPayload(['mqtt_topic' => 'lain/topik', 'mqtt_status_topic' => 'lain/status']))
            ->assertSessionHasNoErrors();

        $config = MqttSettings::current();
        $this->assertSame('omahiot/gateway/+/data', $config['topic']);
        $this->assertSame('omahiot/gateway/+/status', $config['status_topic']);
        $this->assertNull(Setting::find('mqtt_topic'));
    }

    /** @return array<string, mixed> */
    private function mqttPayload(array $overrides = []): array
    {
        return array_merge([
            'mqtt_host' => '10.0.0.5',
            'mqtt_port' => 8883,
            'mqtt_username' => 'omah',
            'mqtt_password' => 'rahasia-123',
            'mqtt_tls' => '1',
        ], $overrides);
    }

    public function test_mqtt_settings_saved_here_are_used_by_subscriber_config(): void
    {
        $this->actingAs($this->user)
            ->post(route('settings.mqttUpdate'), $this->mqttPayload())
            ->assertRedirect(route('settings.system'))
            ->assertSessionHas('success');

        $config = MqttSettings::current();
        $this->assertSame('10.0.0.5', $config['host']);
        $this->assertSame(8883, $config['port']);
        $this->assertSame('omah', $config['username']);
        $this->assertSame('rahasia-123', $config['password']);
        $this->assertTrue($config['tls']);
    }

    public function test_mqtt_password_is_encrypted_and_never_shown_or_logged(): void
    {
        $this->actingAs($this->user)->post(route('settings.mqttUpdate'), $this->mqttPayload());

        $this->assertNotSame('rahasia-123', Setting::find('mqtt_password')->value);
        $this->assertSame('(diubah)', ActivityLog::where('action', 'setting.updated')->latest('id')->first()->properties['after']['mqtt_password']);

        $this->actingAs($this->user)->get(route('settings.system'))->assertDontSee('rahasia-123');
        $this->actingAs($this->user)->get(route('settings.auditLog'))->assertDontSee('rahasia-123');
    }

    public function test_blank_mqtt_password_keeps_the_stored_one(): void
    {
        $this->actingAs($this->user)->post(route('settings.mqttUpdate'), $this->mqttPayload());
        $this->actingAs($this->user)->post(route('settings.mqttUpdate'), $this->mqttPayload(['mqtt_password' => '', 'mqtt_host' => '10.0.0.9']));

        $this->assertSame('rahasia-123', MqttSettings::current()['password']);
        $this->assertSame('10.0.0.9', MqttSettings::current()['host']);
    }

    public function test_mqtt_settings_change_is_detected_for_subscriber_restart(): void
    {
        $before = MqttSettings::fingerprint();

        $this->actingAs($this->user)->post(route('settings.mqttUpdate'), $this->mqttPayload(['mqtt_port' => 1884]));

        $this->assertNotSame($before, MqttSettings::fingerprint());
    }

    public function test_invalid_mqtt_port_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->post(route('settings.mqttUpdate'), $this->mqttPayload(['mqtt_port' => 70000]))
            ->assertSessionHasErrors('mqtt_port');

        $this->assertNull(Setting::find('mqtt_port'));
    }

    public function test_system_settings_are_saved_and_changes_are_logged(): void
    {
        $this->actingAs($this->user)
            ->post(route('settings.systemUpdate'), $this->systemPayload(['data_retention_days' => 180]))
            ->assertRedirect(route('settings.system'))
            ->assertSessionHas('success');

        $this->assertSame('180', Setting::find('data_retention_days')->value);

        // Hanya retensi yang berubah dari default, jadi hanya satu log.
        $retention = ActivityLog::where('action', 'setting.updated')->sole();
        $this->assertSame(['data_retention_days' => '90'], $retention->properties['before']);
        $this->assertSame(['data_retention_days' => '180'], $retention->properties['after']);

        // Primary key Setting berupa string; kuncinya disimpan di properties, bukan di subject_id (bigint).
        $this->assertSame('data_retention_days', $retention->properties['subject_key']);
        $this->assertSame(0, $retention->subject_id);
    }

    public function test_offline_timeout_saved_here_is_used_by_devices(): void
    {
        $this->actingAs($this->user)
            ->post(route('settings.systemUpdate'), $this->systemPayload(['offline_timeout_minutes' => 30]));

        $this->assertSame(30, Device::offlineTimeoutMinutes());
    }

    public function test_invalid_retention_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->post(route('settings.systemUpdate'), $this->systemPayload(['data_retention_days' => 10]))
            ->assertSessionHasErrors('data_retention_days');

        $this->assertSame(0, Setting::count());
    }

    public function test_audit_log_shows_real_activity_with_details(): void
    {
        $gateway = Gateway::create(['name' => 'Gateway Test', 'status' => 'offline', 'mqtt_status' => 'disconnected', 'internet_status' => 'disconnected']);
        $device = Device::create([
            'gateway_id' => $gateway->id, 'node_id' => 1, 'name' => 'Node Sensor 01', 'location' => 'Lokasi A', 'is_active' => true,
        ]);
        ActivityLog::record($this->user->id, 'device.created', $device, null, $device->getAttributes());

        $this->actingAs($this->user)->get(route('settings.auditLog'))
            ->assertOk()
            ->assertSee('device.created')
            ->assertSee('Bayu Tester')
            ->assertSee('Membuat Perangkat #'.$device->id);
    }

    public function test_audit_log_filters_by_user_and_search(): void
    {
        $other = User::factory()->create(['name' => 'Pengguna Lain']);
        ActivityLog::create(['user_id' => $this->user->id, 'action' => 'setting.updated', 'subject_type' => Setting::class, 'subject_id' => 0, 'properties' => []]);
        ActivityLog::create(['user_id' => $other->id, 'action' => 'gateway.deleted', 'subject_type' => Gateway::class, 'subject_id' => 0, 'properties' => []]);

        $this->actingAs($this->user)->get(route('settings.auditLog', ['user_id' => $other->id]))
            ->assertOk()
            ->assertSee('gateway.deleted')
            ->assertDontSee('setting.updated');

        $this->actingAs($this->user)->get(route('settings.auditLog', ['q' => 'setting']))
            ->assertOk()
            ->assertSee('setting.updated')
            ->assertDontSee('gateway.deleted');
    }
}
