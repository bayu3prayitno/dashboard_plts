<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Device;
use App\Models\Gateway;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Dashboard OmahIoT.
 *
 * Membaca data dari tabel devices, measurements, gateways, alerts dan settings.
 * Waktu di database disimpan dalam UTC; tampilan dan pengelompokan memakai zona TZ (WIB).
 */
class DashboardController extends Controller
{
    public const TZ = 'Asia/Jakarta';

    public const SENSORS = [
        'temperature' => ['label' => 'Suhu', 'unit' => '°C', 'digits' => 1],
        'voltage' => ['label' => 'Tegangan', 'unit' => 'V', 'digits' => 2],
        'current' => ['label' => 'Arus', 'unit' => 'A', 'digits' => 2],
        'power' => ['label' => 'Daya', 'unit' => 'W', 'digits' => 1],
        'irradiance' => ['label' => 'Irradiance', 'unit' => 'W/m²', 'digits' => 0],
    ];

    /** Jumlah peringatan aktif yang ditampilkan di kartu dashboard. */
    private const ACTIVE_ALERT_LIMIT = 3;

    /** Nilai status peringatan untuk komponen <x-status-badge>. */
    private const ALERT_STATUS_KEYS = [
        Alert::STATUS_NEW => 'baru',
        Alert::STATUS_ACKNOWLEDGED => 'ditangani',
        Alert::STATUS_RESOLVED => 'selesai',
    ];

    public function index(): View
    {
        $now = CarbonImmutable::now(self::TZ);
        $offlineMinutes = Device::offlineTimeoutMinutes();

        $latest = $this->latestMeasurements();
        $devices = Device::query()
            ->orderBy('node_id')
            ->get()
            ->map(fn (Device $device) => $this->deviceRow($device, $latest->get($device->id), $offlineMinutes))
            ->all();

        $online = array_values(array_filter($devices, fn ($d) => $d['status'] === Device::STATUS_ONLINE));
        $offline = count(array_filter($devices, fn ($d) => $d['status'] === Device::STATUS_OFFLINE));
        $avg = fn (string $key) => $this->average(array_column(array_column($online, 'latest'), $key));

        // Tren untuk grafik: rata-rata semua perangkat aktif
        $activeIds = array_column(array_filter($devices, fn ($d) => $d['aktif']), 'id');
        $trend = $this->trend($activeIds, $now);

        $powerNow = $avg('power');
        $powerPeak = max(array_map(fn ($p) => $p['value'] ?? 0, $trend['power']['24h']));
        $powerShare = $powerNow !== null && $powerPeak > 0 ? min(100, $powerNow / $powerPeak * 100) : 0;

        // Kualitas link: perangkat aktif yang sudah pernah mengirim data
        $links = array_values(array_filter($devices, fn ($d) => $d['aktif'] && $d['packet_loss'] !== null));
        $received = $this->average(array_map(fn ($d) => 100 - $d['packet_loss'], $links));

        return view('dashboard.index', [
            'devices' => $devices,
            'onlineCount' => count($online),
            'offlineCount' => $offline,
            'offlineMinutes' => $offlineMinutes,
            'powerNow' => $powerNow,
            'powerShare' => $powerShare,
            'irradianceNow' => $avg('irradiance'),
            'irradianceSpark' => $trend['irradiance']['24h'],
            'received' => $received,
            'links' => $links,
            'gateway' => $this->gatewayRow(),
            'activeAlertCount' => Alert::open()->count(),
            'activeAlerts' => $this->activeAlerts(),
            'trend' => $trend,
            'sensors' => self::SENSORS,
            'now' => $now,
        ]);
    }

    /** Satu baris perangkat untuk tabel dan daftar di dashboard. */
    private function deviceRow(Device $device, ?object $m, int $offlineMinutes): array
    {
        $latest = [];
        foreach (array_keys(self::SENSORS) as $key) {
            $latest[$key] = $m?->{$key} !== null ? (float) $m->{$key} : null;
        }

        return [
            'id' => $device->id,
            'node_id' => $device->node_id,
            'nama' => $device->name,
            'lokasi' => $device->location,
            'aktif' => $device->is_active,
            'status' => $device->status($offlineMinutes),
            'last_seen' => $device->last_seen_at,
            'packet_loss' => $m?->packet_loss !== null ? (float) $m->packet_loss : null,
            'latest' => $latest,
        ];
    }

    /** Pengukuran terakhir tiap perangkat, dikunci dengan device_id. */
    private function latestMeasurements(): Collection
    {
        return DB::table('measurements as m')
            ->joinSub(
                DB::table('measurements')
                    ->selectRaw('device_id, MAX(recorded_at) AS max_recorded_at')
                    ->groupBy('device_id'),
                'latest',
                fn ($join) => $join
                    ->on('m.device_id', '=', 'latest.device_id')
                    ->on('m.recorded_at', '=', 'latest.max_recorded_at'),
            )
            ->select('m.*')
            ->get()
            ->unique('device_id')
            ->keyBy('device_id');
    }

    /** Gateway utama untuk kartu status. Dipakai nilai kosong bila belum ada gateway. */
    private function gatewayRow(): array
    {
        $gateway = Gateway::query()->oldest('id')->first();

        if (! $gateway) {
            return [
                'nama' => 'Belum ada gateway',
                'status' => 'offline',
                'mqtt_status' => 'disconnected',
                'internet_status' => 'disconnected',
                'last_seen' => null,
            ];
        }

        return [
            'nama' => $gateway->name,
            'status' => $gateway->status,
            'mqtt_status' => $gateway->mqtt_status,
            'internet_status' => $gateway->internet_status,
            'last_seen' => $gateway->last_seen_at,
        ];
    }

    /** Peringatan yang belum selesai, terbaru dulu. */
    private function activeAlerts(): array
    {
        return Alert::open()
            ->with('device:id,name')
            ->latest('triggered_at')
            ->limit(self::ACTIVE_ALERT_LIMIT)
            ->get()
            ->map(fn (Alert $alert) => [
                'id' => $alert->id,
                'device' => $alert->device?->name ?? '-',
                'text' => $alert->message(),
                'waktu' => $alert->triggered_at,
                'status' => self::ALERT_STATUS_KEYS[$alert->status] ?? 'selesai',
            ])
            ->all();
    }

    /**
     * Tren sensor: 24 jam (48 bucket @30 menit) dan 7 hari (rata-rata harian), keduanya rata-rata lintas perangkat aktif.
     *
     * @param  array<int>  $deviceIds
     */
    private function trend(array $deviceIds, CarbonImmutable $now): array
    {
        $days = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        // WIB tidak memakai DST, jadi offset jam tetap; dipakai untuk pengelompokan di SQL
        $offsetHours = intdiv($now->getOffset(), 3600);

        // 48 bucket @30 menit yang berakhir di bucket berjalan (pukul 08:40 → bucket 08:30 paling kanan)
        $currentBucket = $now->setMinute($now->minute >= 30 ? 30 : 0)->startOfMinute();
        $start = $currentBucket->subMinutes(30 * 47);
        $rows24 = $this->averages(
            $deviceIds,
            $start,
            $now,
            "DATE_FORMAT(DATE_ADD(recorded_at, INTERVAL {$offsetHours} HOUR), '%Y-%m-%d %H') AS hour_key, IF(MINUTE(recorded_at) >= 30, 1, 0) AS half_key",
            ['hour_key', 'half_key'],
        );

        $series24 = [];
        for ($i = 0; $i < 48; $i++) {
            $t = $start->addMinutes(30 * $i);
            $series24[] = [
                'label' => $t->format('H:i'),
                'title' => $t->translatedFormat('d M H:i'),
                'row' => $rows24->get($t->format('Y-m-d H').'|'.($t->minute >= 30 ? 1 : 0)),
            ];
        }

        $rows7 = $this->averages(
            $deviceIds,
            $now->subDays(6)->startOfDay(),
            $now,
            "DATE(DATE_ADD(recorded_at, INTERVAL {$offsetHours} HOUR)) AS day_key",
            ['day_key'],
        );

        $series7 = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = $now->subDays($i)->startOfDay();
            $series7[] = [
                'label' => $days[$day->dayOfWeek],
                'title' => $days[$day->dayOfWeek].', '.$day->format('d M'),
                'row' => $rows7->get($day->format('Y-m-d')),
            ];
        }

        $out = [];
        foreach (array_keys(self::SENSORS) as $key) {
            $value = fn ($row) => $row?->{$key} !== null ? round((float) $row->{$key}, 2) : null;

            $out[$key] = [
                '24h' => array_map(fn ($b) => ['label' => $b['label'], 'title' => $b['title'], 'value' => $value($b['row'])], $series24),
                '7d' => array_map(fn ($b) => ['label' => $b['label'], 'title' => $b['title'], 'value' => $value($b['row'])], $series7),
            ];
        }

        return $out;
    }

    /**
     * Rata-rata semua sensor per grup waktu, hanya dari perangkat yang diberikan.
     *
     * @param  array<int>  $deviceIds
     * @param  array<string>  $groupBy  alias kolom grup dari $groupSql
     * @return Collection<string, object> dikunci dengan nilai grup digabung "|"
     */
    private function averages(array $deviceIds, CarbonImmutable $from, CarbonImmutable $to, string $groupSql, array $groupBy): Collection
    {
        if ($deviceIds === []) {
            return collect();
        }

        $avgSql = implode(', ', array_map(fn (string $key) => "AVG({$key}) AS {$key}", array_keys(self::SENSORS)));

        return DB::table('measurements')
            ->whereIn('device_id', $deviceIds)
            ->whereBetween('recorded_at', [
                $from->utc()->format('Y-m-d H:i:s'),
                $to->utc()->format('Y-m-d H:i:s'),
            ])
            ->selectRaw("{$groupSql}, {$avgSql}")
            ->groupBy($groupBy)
            ->get()
            ->keyBy(fn ($row) => implode('|', array_map(fn (string $col) => $row->{$col}, $groupBy)));
    }

    /** Rata-rata nilai yang tidak null; null bila tidak ada data. */
    private function average(array $values): ?float
    {
        $values = array_values(array_filter($values, fn ($v) => $v !== null));

        return $values === [] ? null : array_sum($values) / count($values);
    }
}
