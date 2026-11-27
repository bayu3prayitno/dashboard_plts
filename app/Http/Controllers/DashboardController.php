<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Device;
use App\Models\Gateway;
use App\Models\Setting;
use App\Support\SettingsCache;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
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
    /** Zona waktu tampilan dari pengaturan sistem (default Asia/Jakarta). Penyimpanan tetap UTC. */
    public static function tz(): string
    {
        $value = SettingsCache::get('timezone', 'Asia/Jakarta');

        return in_array($value, SettingsController::TIMEZONES, true) ? $value : 'Asia/Jakarta';
    }

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

    /** Kunci session gateway yang dipilih di dashboard. */
    private const SESSION_GATEWAY = 'dashboard.gateway_id';

    public function index(Request $request): View
    {
        $now = CarbonImmutable::now(self::tz());
        $offlineMinutes = Device::offlineTimeoutMinutes();

        // Semua isi dashboard (node, ringkasan, tren, peringatan) hanya dari gateway yang dipilih
        $gateways = $this->gatewayRows();
        $selected = $this->selectedGateway($request, $gateways);

        $latest = $this->latestMeasurements();
        $devices = $this->gatewayDevices($selected['id'])
            ->map(fn (Device $device) => $this->deviceRow($device, $latest->get($device->id)))
            ->all();

        $online = array_values(array_filter($devices, fn ($d) => $d['status'] === Device::STATUS_ONLINE));
        $offline = count(array_filter($devices, fn ($d) => $d['status'] === Device::STATUS_OFFLINE));
        $avg = fn (string $key) => $this->average(array_column(array_column($online, 'latest'), $key));

        $activeDevices = [];
        foreach ($devices as $d) {
            if ($d['aktif']) {
                $activeDevices[$d['id']] = $d['nama'];
            }
        }

        $trend = Cache::remember('dashboard_trend_'.($selected['id'] ?? 0).'_'.$now->format('Y-m-d-H'), 300,
            fn () => $this->trend($activeDevices, $now)
        );

        $powerNow = $avg('power');
        $powerPeak = max(array_map(fn ($p) => $p['value'] ?? 0, $trend['avg']['power']['24h']));
        $powerShare = $powerNow !== null && $powerPeak > 0 ? min(100, $powerNow / $powerPeak * 100) : 0;

        // Paket diterima = 100 − packet loss dari payload paket terakhir (tidak dihitung di server)
        $received = $this->average(array_map(
            fn ($d) => $d['aktif'] ? $d['received'] : null,
            $devices
        ));

        // Kualitas sinyal terburuk di antara node aktif: satu node bermasalah sudah perlu diperhatikan
        $qualities = array_filter(array_map(fn ($d) => $d['aktif'] ? $d['quality'] : null, $devices));
        $worstQuality = $qualities === [] ? null
            : self::LINK_LEVELS[max(array_map(fn ($q) => array_search($q, self::LINK_LEVELS, true), $qualities))];

        return view('dashboard.index', [
            'devices' => $devices,
            'onlineCount' => count($online),
            'offlineCount' => $offline,
            'offlineMinutes' => $offlineMinutes,
            'powerNow' => $powerNow,
            'powerShare' => $powerShare,
            'irradianceNow' => $avg('irradiance'),
            'irradianceSpark' => $trend['avg']['irradiance']['24h'],
            'received' => $received,
            'worstQuality' => $worstQuality,
            'gateways' => $gateways,
            'gateway' => $selected,
            'activeAlertCount' => $this->openAlerts($selected['id'])->count(),
            'activeAlerts' => $this->activeAlerts($selected['id']),
            'trendDevices' => $trend['devices'],
            'sensors' => self::SENSORS,
            'now' => $now,
        ]);
    }

    /** Satu baris perangkat untuk tabel dan daftar di dashboard. */
    private function deviceRow(Device $device, ?object $m): array
    {
        $latest = [];
        foreach (array_keys(self::SENSORS) as $key) {
            $latest[$key] = $m?->{$key} !== null ? (float) $m->{$key} : null;
        }
        $packetLoss = $m?->packet_loss !== null ? (float) $m->packet_loss : null;

        return [
            'id' => $device->id,
            'node_id' => $device->node_id,
            'nama' => $device->name,
            'lokasi' => $device->location,
            'aktif' => $device->is_active,
            'status' => $device->status(),
            'last_seen' => $device->last_seen_at,
            'packet_loss' => $packetLoss,
            'received' => $packetLoss !== null ? max(0, 100 - $packetLoss) : null,
            'rssi' => $m?->rssi !== null ? (int) $m->rssi : null,
            'snr' => $m?->snr !== null ? (float) $m->snr : null,
            'quality' => self::linkQuality($m?->rssi !== null ? (int) $m->rssi : null, $m?->snr !== null ? (float) $m->snr : null),
            'latest' => $latest,
        ];
    }

    /** Level kualitas sinyal LoRa, dari terbaik ke terburuk. */
    public const LINK_LEVELS = ['sangat_baik', 'baik', 'cukup', 'buruk', 'kritis'];

    /**
     * Kualitas sinyal LoRa dari paket terakhir: diambil yang terburuk antara RSSI dan SNR.
     * Ambang mengikuti HSLU LoRa Field Testing Guideline
     * (https://hslu-ige-laes.github.io/lora-devices-ttn/docs/field_testing_guideline):
     */
    public static function linkQuality(?int $rssi, ?float $snr): ?string
    {
        if ($rssi === null && $snr === null) {
            return null;
        }

        $byRssi = $rssi === null ? 0 : match (true) {
            $rssi > -90 => 0,
            $rssi >= -100 => 1,
            $rssi >= -115 => 2,
            $rssi >= -125 => 3,
            default => 4,
        };
        $bySnr = $snr === null ? 0 : match (true) {
            $snr > 5 => 0,
            $snr >= 0 => 1,
            $snr >= -7 => 2,
            $snr >= -15 => 3,
            default => 4,
        };

        return self::LINK_LEVELS[max($byRssi, $bySnr)];
    }

    /** Pengukuran terakhir tiap perangkat, dikunci dengan device_id. */
    private function latestMeasurements(): Collection
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            return $this->latestMeasurementsMySQL();
        }

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

    private function latestMeasurementsMySQL(): Collection
    {
        return DB::table(DB::raw(<<<'SQL'
            (SELECT * FROM measurements
             WHERE (device_id, recorded_at) IN (
                SELECT device_id, MAX(recorded_at)
                FROM measurements
                GROUP BY device_id
             )) AS m
        SQL
        ))
            ->select('m.*')
            ->get()
            ->keyBy('device_id');
    }

    /**
     * Semua gateway untuk kartu status (gateway pertama tampil lebih dulu, sisanya lewat dropdown).
     * Berisi satu baris kosong bila belum ada gateway.
     *
     * @return array<int, array{id: ?int, nama: string, status: string, mqtt_status: string, internet_status: string, last_seen: mixed}>
     */
    private function gatewayRows(): array
    {
        $rows = Gateway::query()->oldest('id')->get()->map(fn (Gateway $gateway) => [
            'id' => $gateway->id,
            'nama' => $gateway->name,
            'status' => $gateway->status,
            'mqtt_status' => $gateway->mqtt_status,
            'internet_status' => $gateway->internet_status,
            'last_seen' => $gateway->last_seen_at,
        ])->all();

        return $rows ?: [[
            'id' => null,
            'nama' => 'Belum ada gateway',
            'status' => 'offline',
            'mqtt_status' => 'disconnected',
            'internet_status' => 'disconnected',
            'last_seen' => null,
        ]];
    }

    /**
     * Gateway yang dipilih: dari ?gateway=ID, lalu pilihan terakhir di session, lalu gateway pertama.
     * Pilihan yang valid disimpan di session agar tetap terpilih saat dashboard dibuka lagi.
     *
     * @param  array<int, array{id: ?int}>  $gateways
     */
    private function selectedGateway(Request $request, array $gateways): array
    {
        $byId = array_column($gateways, null, 'id');
        $wanted = $request->integer('gateway') ?: (int) $request->session()->get(self::SESSION_GATEWAY);

        if (! isset($byId[$wanted])) {
            return $gateways[0];
        }

        $request->session()->put(self::SESSION_GATEWAY, $wanted);

        return $byId[$wanted];
    }

    /** Node milik gateway tertentu, urut node_id. Kosong bila belum ada gateway. */
    private function gatewayDevices(?int $gatewayId): Collection
    {
        if ($gatewayId === null) {
            return collect();
        }

        return Device::query()->where('gateway_id', $gatewayId)->orderBy('node_id')->get();
    }

    /** Peringatan yang belum selesai dari node milik gateway tertentu. */
    private function openAlerts(?int $gatewayId): Builder
    {
        return Alert::open()->whereHas('device', fn (Builder $q) => $q->where('gateway_id', $gatewayId));
    }

    /** Peringatan yang belum selesai dari gateway tertentu, terbaru dulu. */
    private function activeAlerts(?int $gatewayId): array
    {
        return $this->openAlerts($gatewayId)
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
     * Tren sensor, dua bentuk:
     * - 'avg': rata-rata lintas perangkat aktif (dipakai sparkline irradiance dan skala daya).
     * - 'devices': satu seri per perangkat aktif, agar tiap perangkat tampil sebagai garis sendiri.
     * Rentang 24 jam = 48 bucket @30 menit; 7 hari = rata-rata harian.
     *
     * @param  array<int, string>  $devices  id perangkat aktif => nama
     */
    private function trend(array $devices, CarbonImmutable $now): array
    {
        $days = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        // WIB tidak memakai DST, jadi offset jam tetap; dipakai untuk pengelompokan di SQL
        $offsetHours = intdiv($now->getOffset(), 3600);
        $deviceIds = array_keys($devices);

        // 48 bucket @30 menit yang berakhir di bucket berjalan (pukul 08:40 → bucket 08:30 paling kanan)
        $currentBucket = $now->setMinute($now->minute >= 30 ? 30 : 0)->startOfMinute();
        $start = $currentBucket->subMinutes(30 * 47);

        $slots24 = [];
        for ($i = 0; $i < 48; $i++) {
            $t = $start->addMinutes(30 * $i);
            $slots24[] = [
                'key' => $t->format('Y-m-d H').'|'.($t->minute >= 30 ? 1 : 0),
                'label' => $t->format('H:i'),
                'title' => $t->translatedFormat('d M H:i'),
            ];
        }

        $slots7 = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = $now->subDays($i)->startOfDay();
            $slots7[] = [
                'key' => $day->format('Y-m-d'),
                'label' => $days[$day->dayOfWeek],
                'title' => $days[$day->dayOfWeek].', '.$day->format('d M'),
            ];
        }

        $group24 = "DATE_FORMAT(DATE_ADD(recorded_at, INTERVAL {$offsetHours} HOUR), '%Y-%m-%d %H') AS hour_key, IF(MINUTE(recorded_at) >= 30, 1, 0) AS half_key";
        $group7 = "DATE(DATE_ADD(recorded_at, INTERVAL {$offsetHours} HOUR)) AS day_key";

        // Rata-rata lintas perangkat
        $byTime24 = $this->averages($deviceIds, $start, $now, $group24, ['hour_key', 'half_key']);
        $byTime7 = $this->averages($deviceIds, $now->subDays(6)->startOfDay(), $now, $group7, ['day_key']);

        // Per perangkat (kunci: device_id|...)
        $perDevice24 = $this->averages($deviceIds, $start, $now, "device_id, {$group24}", ['device_id', 'hour_key', 'half_key']);
        $perDevice7 = $this->averages($deviceIds, $now->subDays(6)->startOfDay(), $now, "device_id, {$group7}", ['device_id', 'day_key']);

        $avg = [];
        $devicesOut = [];
        foreach (array_keys(self::SENSORS) as $key) {
            $value = fn ($row) => $row?->{$key} !== null ? round((float) $row->{$key}, 2) : null;

            $avg[$key] = [
                '24h' => array_map(fn ($s) => ['label' => $s['label'], 'title' => $s['title'], 'value' => $value($byTime24->get($s['key']))], $slots24),
                '7d' => array_map(fn ($s) => ['label' => $s['label'], 'title' => $s['title'], 'value' => $value($byTime7->get($s['key']))], $slots7),
            ];

            $devicesOut[$key] = [
                '24h' => $this->deviceSeries($devices, $slots24, $perDevice24, $key),
                '7d' => $this->deviceSeries($devices, $slots7, $perDevice7, $key),
            ];
        }

        return ['avg' => $avg, 'devices' => $devicesOut];
    }

    /**
     * Satu seri per perangkat untuk satu sensor dan satu rentang.
     *
     * @param  array<int, string>  $devices  id => nama
     * @param  array<int, array{key: string, label: string, title: string}>  $slots
     * @param  Collection<string, object>  $rows  dikunci "device_id|kunci slot"
     */
    private function deviceSeries(array $devices, array $slots, Collection $rows, string $key): array
    {
        $series = [];
        foreach ($devices as $id => $name) {
            $data = [];
            foreach ($slots as $slot) {
                $row = $rows->get($id.'|'.$slot['key']);
                $data[] = $row?->{$key} !== null ? round((float) $row->{$key}, 2) : null;
            }
            $series[] = ['name' => $name, 'data' => $data];
        }

        return [
            'labels' => array_column($slots, 'label'),
            'titles' => array_column($slots, 'title'),
            'series' => $series,
        ];
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

    /** API untuk polling: return data dashboard terbaru (devices, status, measurements). */
    public function liveData(Request $request): \Illuminate\Http\JsonResponse
    {
        $gateway = $this->selectedGateway($request, $this->gatewayRows());
        $latest = $this->latestMeasurements();
        $devices = $this->gatewayDevices($gateway['id'])
            ->map(fn (Device $device) => $this->deviceRow($device, $latest->get($device->id)))
            ->all();

        $online = array_values(array_filter($devices, fn ($d) => $d['status'] === Device::STATUS_ONLINE));
        $avg = fn (string $key) => $this->average(array_column(array_column($online, 'latest'), $key));

        return response()->json([
            'devices' => $devices,
            'alerts' => Cache::remember('dashboard_alerts_live_'.($gateway['id'] ?? 0), 30, fn () => $this->activeAlerts($gateway['id'])),
            'onlineCount' => count($online),
            'offlineCount' => count($devices) - count($online),
            'powerNow' => $avg('power'),
            'temperatureNow' => $avg('temperature'),
            'gateway' => $gateway,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
