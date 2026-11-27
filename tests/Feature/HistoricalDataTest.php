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

        // satu seri per node untuk sensor yang dipilih, tidak dirata-rata lintas node
        $response->assertJsonCount(2, 'datasets');
        $response->assertJsonPath('datasets.0.sensor', 'temperature');
        $response->assertJsonPath('datasets.0.label', 'Node Sensor 01');
        $response->assertJsonPath('datasets.0.data.0.y', 28.5);
        $response->assertJsonPath('datasets.1.label', 'Node Sensor 02');
        $response->assertJsonPath('datasets.1.data.0.y', 30.5);
        // kedua node di sumbu waktu yang sama (epoch ms)
        $x = strtotime('2026-11-14 03:00:00 UTC') * 1000;
        $response->assertJsonPath('datasets.0.data.0.x', $x);
        $response->assertJsonPath('datasets.1.data.0.x', $x);
    }

    public function test_latest_chart_aligns_nodes_sending_seconds_apart_in_same_round(): void
    {
        // Siklus 1: node A 10:00:00, node B 10:00:07 → satu putaran
        $this->measurement($this->deviceA, 1, '2026-11-14 03:00:00', temp: 28);
        $this->measurement($this->deviceB, 1, '2026-11-14 03:00:07', temp: 31);
        // Siklus 2 (30 menit kemudian): node B duluan, A menyusul 12 detik
        $this->measurement($this->deviceB, 2, '2026-11-14 03:30:03', temp: 32);
        $this->measurement($this->deviceA, 2, '2026-11-14 03:30:15', temp: 29);

        $json = $this->actingAs($this->user)
            ->getJson(route('historical.chartData', ['sensor_type' => 'temperature']))
            ->assertOk()
            ->json();

        [$a, $b] = $json['datasets'];
        $round1 = strtotime('2026-11-14 03:00:00 UTC') * 1000;
        $round2 = strtotime('2026-11-14 03:30:03 UTC') * 1000;

        $this->assertSame([$round1, $round2], $json['meta']['ticks']);
        $this->assertSame([$round1, $round2], array_column($a['data'], 'x'));
        $this->assertSame([$round1, $round2], array_column($b['data'], 'x'));
        $this->assertEquals([28.0, 29.0], array_column($a['data'], 'y'));
        $this->assertEquals([31.0, 32.0], array_column($b['data'], 'y'));
    }

    public function test_latest_chart_shows_only_last_ten_rounds_and_hides_nodes_not_sending(): void
    {
        // Node B berhenti lebih dulu; node A masih mengirim 10 putaran sesudahnya
        for ($i = 0; $i < 10; $i++) {
            $this->measurement($this->deviceB, $i + 1, sprintf('2026-11-14 01:%02d:00', $i * 5), temp: 30 + $i);
            $this->measurement($this->deviceA, $i + 1, sprintf('2026-11-14 03:%02d:00', $i * 5), temp: 20 + $i);
        }

        $json = $this->actingAs($this->user)
            ->getJson(route('historical.chartData', ['sensor_type' => 'temperature']))
            ->assertOk()
            ->json();

        // 10 data terakhir semuanya dari node A → node B tidak ditampilkan
        $this->assertCount(10, $json['meta']['ticks']);
        $this->assertCount(1, $json['datasets']);
        $this->assertSame($this->deviceA->id, $json['datasets'][0]['device_id']);
        $this->assertCount(10, $json['datasets'][0]['data']);
    }

    public function test_chart_data_with_date_range_aligns_nodes_on_same_intervals(): void
    {
        // 10:00 & 10:30 WIB → interval pertama dan kedua dari rentang 10:00–15:00 (10 x 30 menit)
        $this->measurement($this->deviceA, 1, '2026-11-14 03:00:00', temp: 20);
        $this->measurement($this->deviceA, 2, '2026-11-14 03:10:00', temp: 22);
        $this->measurement($this->deviceB, 1, '2026-11-14 03:40:00', temp: 30);

        $json = $this->actingAs($this->user)
            ->getJson(route('historical.chartData', [
                'sensor_type' => 'temperature',
                'date_from' => '2026-11-14T10:00',
                'date_to' => '2026-11-14T15:00',
            ]))
            ->assertOk()
            ->json();

        $this->assertSame('range', $json['meta']['mode']);
        $this->assertCount(10, $json['meta']['ticks']);
        [$a, $b] = $json['datasets'];

        $this->assertSame(array_column($a['data'], 'x'), array_column($b['data'], 'x'));
        $this->assertSame($json['meta']['ticks'], array_column($a['data'], 'x'));
        $this->assertEquals([21.0, null], array_slice(array_column($a['data'], 'y'), 0, 2));
        $this->assertEquals([null, 30.0], array_slice(array_column($b['data'], 'y'), 0, 2));
    }

    public function test_chart_style_index_stays_the_same_when_filtered_by_device(): void
    {
        $this->measurement($this->deviceB, 1, '2026-11-14 03:00:00');

        $all = $this->actingAs($this->user)->getJson(route('historical.chartData', ['sensor_type' => 'power']))->json();
        $one = $this->actingAs($this->user)->getJson(route('historical.chartData', [
            'sensor_type' => 'power', 'device_id' => $this->deviceB->id,
        ]))->json();

        $this->assertSame(1, collect($all['datasets'])->firstWhere('device_id', $this->deviceB->id)['style']);
        $this->assertCount(1, $one['datasets']);
        $this->assertSame(1, $one['datasets'][0]['style']);
    }

    private function secondGatewayDevice(): Device
    {
        $gateway = Gateway::create([
            'name' => 'Gateway Kedua',
            'status' => 'offline',
            'mqtt_status' => 'disconnected',
            'internet_status' => 'disconnected',
        ]);

        return Device::create([
            'gateway_id' => $gateway->id, 'node_id' => 1, 'name' => 'Node Gateway Kedua', 'location' => 'Lokasi C', 'is_active' => true,
        ]);
    }

    public function test_default_shows_only_first_gateway(): void
    {
        $other = $this->secondGatewayDevice();
        $this->measurement($this->deviceA, 1, '2026-11-14 03:00:00');
        $this->measurement($other, 2, '2026-11-14 03:00:00');

        $this->actingAs($this->user)->get(route('historical.index'))
            ->assertOk()
            ->assertViewHas('filters', fn (array $f) => $f['gateway_id'] === $this->deviceA->gateway_id)
            ->assertViewHas('data', fn (array $rows) => count($rows) === 1 && $rows[0]['packet_number'] === 1);

        $chart = $this->actingAs($this->user)->getJson(route('historical.chartData', ['sensor_type' => 'power']))->json();
        $this->assertSame([$this->deviceA->id], array_column($chart['datasets'], 'device_id'));

        $csv = $this->actingAs($this->user)->get(route('historical.exportCsv'))->streamedContent();
        $this->assertStringNotContainsString('Node Gateway Kedua', $csv);
    }

    public function test_gateway_filter_selects_other_gateway(): void
    {
        $other = $this->secondGatewayDevice();
        $this->measurement($this->deviceA, 1, '2026-11-14 03:00:00');
        $this->measurement($other, 2, '2026-11-14 03:00:00');

        $this->actingAs($this->user)
            ->get(route('historical.index', ['gateway_id' => $other->gateway_id]))
            ->assertOk()
            ->assertViewHas('data', fn (array $rows) => count($rows) === 1 && $rows[0]['packet_number'] === 2);

        // Style dihitung per gateway: node pertama gateway kedua tetap warna pertama
        $chart = $this->actingAs($this->user)
            ->getJson(route('historical.chartData', ['sensor_type' => 'power', 'gateway_id' => $other->gateway_id]))
            ->json();
        $this->assertSame([$other->id], array_column($chart['datasets'], 'device_id'));
        $this->assertSame(0, $chart['datasets'][0]['style']);
    }

    public function test_device_from_other_gateway_is_rejected(): void
    {
        $other = $this->secondGatewayDevice();

        $this->actingAs($this->user)
            ->get(route('historical.index', ['gateway_id' => $this->deviceA->gateway_id, 'device_id' => $other->id]))
            ->assertSessionHasErrors('device_id');
    }

    public function test_device_filter_without_gateway_uses_device_gateway(): void
    {
        $other = $this->secondGatewayDevice();
        $this->measurement($other, 2, '2026-11-14 03:00:00');

        $this->actingAs($this->user)
            ->get(route('historical.index', ['device_id' => $other->id]))
            ->assertOk()
            ->assertViewHas('filters', fn (array $f) => $f['gateway_id'] === $other->gateway_id)
            ->assertViewHas('data', fn (array $rows) => count($rows) === 1);
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
