<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Gateway;
use App\Models\Measurement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoricalDataTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Device $deviceA;

    private Device $deviceB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $gateway = Gateway::create([
            'name' => 'Gateway Test',
            'location' => 'Lokasi A',
            'status' => 'offline',
            'mqtt_status' => 'disconnected',
            'internet_status' => 'disconnected',
        ]);
        $this->deviceA = Device::create([
            'gateway_id' => $gateway->id, 'node_id' => 1, 'name' => 'Node Sensor 01', 'location' => 'Lokasi A', 'is_active' => true,
        ]);
        $this->deviceB = Device::create([
            'gateway_id' => $gateway->id, 'node_id' => 2, 'name' => 'Node Sensor 02', 'location' => 'Lokasi B', 'is_active' => true,
        ]);
    }

    private function measurement(Device $device, int $packet, string $recordedAtUtc, float $temp = 28.5, float $loss = 2.5): void
    {
        Measurement::create([
            'device_id' => $device->id,
            'packet_no' => $packet,
            'temperature' => $temp,
            'voltage' => 220.5,
            'current' => 5.2,
            'power' => 1146,
            'irradiance' => 850,
            'rssi' => -85,
            'snr' => 10,
            'packet_loss' => $loss,
            'recorded_at' => $recordedAtUtc,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('historical.index'))->assertRedirect(route('login'));
    }

    public function test_index_shows_only_real_measurements(): void
    {
        // 10:00 WIB = 03:00 UTC
        $this->measurement($this->deviceA, 1001, '2026-11-14 03:00:00');

        $this->actingAs($this->user)->get(route('historical.index'))
            ->assertOk()
            ->assertSee('Node Sensor 01')
            ->assertSee('1001')
            ->assertSee('2026-11-14 10:00:00')
            ->assertDontSee('DEVICE_001');
    }

    public function test_index_shows_empty_state_when_no_data(): void
    {
        $this->actingAs($this->user)->get(route('historical.index'))
            ->assertOk()
            ->assertSee('Tidak ada data yang ditemukan');
    }

    public function test_device_filter_limits_rows(): void
    {
        $this->measurement($this->deviceA, 1, '2026-11-14 03:00:00');
        $this->measurement($this->deviceB, 2, '2026-11-14 03:00:00');

        // Dropdown perangkat selalu memuat semua nama, jadi cek isi data tabel saja
        $this->actingAs($this->user)
            ->get(route('historical.index', ['device_id' => $this->deviceB->id]))
            ->assertOk()
            ->assertViewHas('data', fn (array $rows) => count($rows) === 1 && $rows[0]['packet_number'] === 2);
    }

    public function test_date_range_uses_wib_day_boundaries(): void
    {
        // 14 Nov 00:30 WIB = 13 Nov 17:30 UTC (masuk rentang 14 Nov di WIB)
        $this->measurement($this->deviceA, 1, '2026-11-13 17:30:00');
        // 15 Nov 00:30 WIB = 14 Nov 17:30 UTC (di luar rentang)
        $this->measurement($this->deviceA, 2, '2026-11-14 17:30:00');

        $this->actingAs($this->user)
            ->get(route('historical.index', ['date_from' => '2026-11-14', 'date_to' => '2026-11-14']))
            ->assertOk()
            ->assertViewHas('data', fn (array $rows) => count($rows) === 1 && $rows[0]['timestamp'] === '2026-11-14 00:30:00');
    }

    public function test_invalid_date_range_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->get(route('historical.index', ['date_from' => '2026-11-14', 'date_to' => '2026-11-01']))
            ->assertSessionHasErrors('date_to');
    }

    public function test_unknown_sensor_type_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->get(route('historical.index', ['sensor_type' => 'humidity']))
            ->assertSessionHasErrors('sensor_type');
    }

    public function test_stats_show_total_and_average_packet_loss(): void
    {
        $this->measurement($this->deviceA, 1, '2026-11-14 03:00:00', loss: 2.0);
        $this->measurement($this->deviceA, 2, '2026-11-14 03:05:00', loss: 4.0);

        $response = $this->actingAs($this->user)->get(route('historical.index'))->assertOk();
        $response->assertViewHas('stats', fn (array $s) => $s['total'] === 2 && $s['avg_packet_loss'] === 3.0);
    }

    public function test_csv_export_contains_header_and_rows_in_order(): void
    {
        $this->measurement($this->deviceA, 2, '2026-11-14 03:05:00');
        $this->measurement($this->deviceA, 1, '2026-11-14 03:00:00');

        $response = $this->actingAs($this->user)->get(route('historical.exportCsv'));
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content); // BOM UTF-8

        $rows = array_map('str_getcsv', array_values(array_filter(explode("\n", substr($content, 3)))));
        $this->assertSame('Timestamp', $rows[0][0]);
        $this->assertSame('Packet Loss (%)', $rows[0][11]);
        $this->assertSame(['2026-11-14 10:00:00', '1', '1', 'Node Sensor 01'], array_slice($rows[1], 0, 4));
        $this->assertSame(['2026-11-14 10:05:00', '2', '1', 'Node Sensor 01'], array_slice($rows[2], 0, 4));
    }

    public function test_chart_data_returns_series_for_selected_sensor(): void
    {
        $this->measurement($this->deviceA, 1, '2026-11-14 03:00:00', temp: 28.5);
        $this->measurement($this->deviceB, 1, '2026-11-14 03:00:00', temp: 30.5);

        $response = $this->actingAs($this->user)
            ->getJson(route('historical.chartData', ['sensor_type' => 'temperature']))
            ->assertOk();

        $response->assertJsonCount(1, 'datasets');
        $response->assertJsonPath('datasets.0.sensor', 'temperature');
        // dua perangkat pada waktu yang sama dirata-rata menjadi satu titik
        $response->assertJsonPath('datasets.0.data.0', 29.5);
        $response->assertJsonPath('labels.0', '14/11 10:00');
    }

    public function test_filter_endpoint_returns_json_rows(): void
    {
        $this->measurement($this->deviceA, 1, '2026-11-14 03:00:00');

        $this->actingAs($this->user)
            ->postJson(route('historical.filter'), ['device_id' => $this->deviceA->id])
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.device_name', 'Node Sensor 01');
    }
}
