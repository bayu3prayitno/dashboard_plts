<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Alert;
use App\Models\Device;
use App\Models\Gateway;
use App\Models\Setting;
use App\Models\Threshold;
use App\Models\User;
use App\Services\TelemetryIngestor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Gateway $gateway;

    private Device $deviceA;

    private Device $deviceB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->gateway = Gateway::create([
            'name' => 'Gateway Test',
        ]);
        $this->deviceA = Device::create([
            'gateway_id' => $this->gateway->id, 'node_id' => 1, 'name' => 'Node Sensor 01', 'location' => 'Lokasi A', 'is_active' => true,
        ]);
        $this->deviceB = Device::create([
            'gateway_id' => $this->gateway->id, 'node_id' => 2, 'name' => 'Node Sensor 02', 'location' => 'Lokasi B', 'is_active' => true,
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private function ingest(int $node, int $packet, array $overrides = []): void
    {
        $payload = array_merge([
            'node_id' => $node,
            'packet_no' => $packet,
            'temperature' => 28.5,
            'voltage' => 220.5, // sesuai ambang tegangan default (190–240 V)
            'current' => 1.2,
            'power' => 14.5,
            'irradiance' => 650,
            'rssi' => -72,
            'snr' => 8.5,
            'packet_loss' => 0,
            'recorded_at' => '2026-11-15 10:00:00',
        ], $overrides);

        app(TelemetryIngestor::class)->ingest($this->gateway->gateway_code, $payload);
    }

    public function test_value_above_max_creates_threshold_alert(): void
    {
        $this->ingest(1, 1, ['temperature' => 40]);

        $alert = Alert::sole();
        $this->assertSame($this->deviceA->id, $alert->device_id);
        $this->assertSame(Alert::TYPE_THRESHOLD_HIGH, $alert->alert_type);
        $this->assertSame('temperature', $alert->sensor_type);
        $this->assertSame(Alert::STATUS_NEW, $alert->status);
        $this->assertEquals(40.0, (float) $alert->recorded_value);
        $this->assertEquals(35.0, (float) $alert->threshold_value);
        $this->assertSame('Suhu 40°C di atas ambang 35°C', $alert->message());
    }

    public function test_value_below_min_creates_low_threshold_alert(): void
    {
        $this->ingest(1, 1, ['temperature' => 5]);

        $alert = Alert::sole();
        $this->assertSame(Alert::TYPE_THRESHOLD_LOW, $alert->alert_type);
        $this->assertEquals(15.0, (float) $alert->threshold_value);
    }

    public function test_normal_values_create_no_alert(): void
    {
        $this->ingest(1, 1);

        $this->assertSame(0, Alert::count());
    }

    public function test_open_alert_is_not_duplicated_until_resolved(): void
    {
        $this->ingest(1, 1, ['temperature' => 40]);
        $this->ingest(1, 2, ['temperature' => 41]);
        $this->assertSame(1, Alert::count());

        Alert::sole()->update(['status' => Alert::STATUS_RESOLVED]);

        $this->ingest(1, 3, ['temperature' => 42]);
        $this->assertSame(2, Alert::count());
    }

    public function test_device_threshold_overrides_global_threshold(): void
    {
        Threshold::create([
            'device_id' => $this->deviceA->id, 'sensor_type' => 'temperature', 'min_value' => 0, 'max_value' => 50, 'is_active' => true,
        ]);

        $this->ingest(1, 1, ['temperature' => 40]); // di atas ambang global, tapi di bawah ambang khusus perangkat A
        $this->assertSame(0, Alert::count());

        $this->ingest(2, 1, ['temperature' => 40]); // perangkat B tetap memakai ambang global
        $this->assertSame(1, Alert::count());
    }

    public function test_offline_device_gets_alert_and_alert_closes_when_data_returns(): void
    {
        $this->deviceA->update(['last_seen_at' => now()->subMinutes(30)]);
        // deviceB belum pernah mengirim data (last_seen_at kosong) dan tidak boleh diberi alert offline

        $this->artisan('alerts:check-offline')->assertSuccessful();

        $alert = Alert::where('alert_type', Alert::TYPE_OFFLINE)->sole();
        $this->assertSame($this->deviceA->id, $alert->device_id);
        $this->assertSame(Alert::STATUS_NEW, $alert->status);

        $this->ingest(1, 1);

        $alert->refresh();
        $this->assertSame(Alert::STATUS_RESOLVED, $alert->status);
        $this->assertNotNull($alert->resolved_at);
    }

    public function test_offline_check_does_not_duplicate_open_alert(): void
    {
        $this->deviceA->update(['last_seen_at' => now()->subMinutes(30)]);

        $this->artisan('alerts:check-offline');
        $this->artisan('alerts:check-offline');

        $this->assertSame(1, Alert::where('alert_type', Alert::TYPE_OFFLINE)->count());
    }

    public function test_device_within_timeout_is_not_offline(): void
    {
        $this->deviceA->update(['last_seen_at' => now()->subMinutes(5)]);

        $this->artisan('alerts:check-offline');

        $this->assertSame(0, Alert::count());
    }

    public function test_status_can_be_acknowledged_and_resolved(): void
    {
        $this->ingest(1, 1, ['temperature' => 40]);
        $alert = Alert::sole();

        $this->actingAs($this->user)
            ->postJson(route('alerts.updateStatus', $alert), ['status' => Alert::STATUS_ACKNOWLEDGED])
            ->assertOk()
            ->assertJson(['success' => true, 'status' => Alert::STATUS_ACKNOWLEDGED]);

        $alert->refresh();
        $this->assertSame($this->user->id, $alert->handled_by);
        $this->assertNull($alert->resolved_at);

        $this->actingAs($this->user)
            ->postJson(route('alerts.updateStatus', $alert), ['status' => Alert::STATUS_RESOLVED, 'resolution_note' => 'Kipas dinyalakan'])
            ->assertOk();

        $alert->refresh();
        $this->assertSame(Alert::STATUS_RESOLVED, $alert->status);
        $this->assertNotNull($alert->resolved_at);
        $this->assertSame('Kipas dinyalakan', $alert->resolution_note);
    }

    public function test_invalid_status_is_rejected(): void
    {
        $this->ingest(1, 1, ['temperature' => 40]);

        $this->actingAs($this->user)
            ->postJson(route('alerts.updateStatus', Alert::sole()), ['status' => 'pending'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_alert_page_lists_alerts_from_database(): void
    {
        $this->ingest(1, 1, ['temperature' => 40]);

        $this->actingAs($this->user)->get(route('alerts.index'))
            ->assertOk()
            ->assertSee('Node Sensor 01')
            ->assertSee('Threshold Breach')
            ->assertSee('Suhu 40°C di atas ambang 35°C');
    }

    public function test_alert_page_filters_by_status(): void
    {
        $this->ingest(1, 1, ['temperature' => 40]);
        $this->ingest(2, 1, ['temperature' => 5]);
        Alert::where('device_id', $this->deviceB->id)->update(['status' => Alert::STATUS_RESOLVED]);

        $this->actingAs($this->user)->get(route('alerts.index', ['status' => 'new']))
            ->assertOk()
            ->assertSee('Node Sensor 01')
            ->assertDontSee('Node Sensor 02');
    }

    public function test_configuration_saves_timeout_and_thresholds_and_logs_changes(): void
    {
        $this->actingAs($this->user)
            ->post(route('alerts.configuration.update'), $this->configPayload(timeout: 20, tempMax: 40))
            ->assertRedirect(route('alerts.configuration'))
            ->assertSessionHas('success');

        $this->assertSame('20', Setting::find('offline_timeout_minutes')->value);
        $this->assertEquals(40.0, (float) Threshold::whereNull('device_id')->where('sensor_type', 'temperature')->value('max_value'));
        $this->assertSame(1, ActivityLog::where('action', 'threshold.updated')->count());
        $this->assertSame(1, ActivityLog::where('action', 'setting.updated')->count());
    }

    public function test_configuration_rejects_max_below_min(): void
    {
        $this->actingAs($this->user)
            ->post(route('alerts.configuration.update'), $this->configPayload(tempMin: 30, tempMax: 10))
            ->assertSessionHasErrors('thresholds.temperature.max');
    }

    public function test_configuration_page_renders(): void
    {
        $this->actingAs($this->user)->get(route('alerts.configuration'))
            ->assertOk()
            ->assertSee('Offline Timeout')
            ->assertSee('Suhu');
    }

    /** @return array<string, mixed> */
    private function configPayload(int $timeout = 15, float $tempMin = 15, float $tempMax = 35): array
    {
        return [
            'offline_timeout' => $timeout,
            'thresholds' => [
                'temperature' => ['min' => $tempMin, 'max' => $tempMax],
                'voltage' => ['min' => 190, 'max' => 240],
                'current' => ['min' => 0, 'max' => 10],
                'power' => ['min' => 0, 'max' => 2500],
                'irradiance' => ['min' => 0, 'max' => 1200],
            ],
        ];
    }
}
