<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Device;
use App\Models\Gateway;
use App\Models\Setting;
use App\Models\User;
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
            ->assertDontSee('MQTT');
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
