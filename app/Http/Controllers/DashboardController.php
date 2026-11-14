<?php

namespace App\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\View\View;

/**
 * Dashboard OmahIoT.
 *
 * Data masih tiruan (deterministik, mengikuti pola matahari harian) dan disusun dengan
 * struktur yang sama seperti nanti dari database: devices, gateway, alerts, sensor_data.
 * Ganti metode-metode mock di bawah dengan query Eloquent saat backend siap.
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

    public function index(): View
    {
        $now = CarbonImmutable::now(self::TZ);
        $offlineMinutes = 15;

        $devices = self::withStatus(self::mockDevices($now), $now, $offlineMinutes);
        foreach ($devices as &$d) {
            $d['latest'] = $this->sample($d['id'], $d['last_seen']);
        }
        unset($d);

        $online = array_values(array_filter($devices, fn ($d) => $d['status'] === 'online'));
        $offline = count(array_filter($devices, fn ($d) => $d['status'] === 'offline'));
        $avg = fn (string $key) => count($online)
            ? array_sum(array_map(fn ($d) => $d['latest'][$key], $online)) / count($online)
            : null;

        // Seri tren untuk grafik (rata-rata semua node aktif)
        $trend = $this->trend($devices, $now);

        $powerNow = $avg('power');
        $powerPeak = max(array_map(fn ($p) => $p['value'] ?? 0, $trend['power']['24h']) ?: [0]);
        $powerShare = $powerNow !== null && $powerPeak > 0 ? min(100, $powerNow / $powerPeak * 100) : 0;

        $links = array_values(array_filter($devices, fn ($d) => $d['aktif']));
        $received = count($links)
            ? array_sum(array_map(fn ($d) => 100 - $d['packet_loss'], $links)) / count($links)
            : null;

        $alerts = self::mockAlerts($now);
        $activeAlerts = array_values(array_filter($alerts, fn ($a) => $a['status'] !== 'selesai'));
        $freshAlerts = array_values(array_filter($alerts, fn ($a) => $a['status'] === 'baru'));

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
            'gateway' => self::mockGateway($now),
            'activeAlerts' => $activeAlerts,
            'freshAlerts' => $freshAlerts,
            'trend' => $trend,
            'sensors' => self::SENSORS,
            'now' => $now,
        ]);
    }

    /** Nilai sensor tiruan satu node pada waktu tertentu (pola matahari + awan harian). */
    private function sample(int $node, CarbonImmutable $t): array
    {
        $hour = $t->hour + $t->minute / 60;
        $day = intdiv($t->timestamp + 7 * 3600, 86400);
        $cloud = 0.65 + 0.35 * (crc32("cloud-$node-$day") % 1000) / 1000;
        $noise = (crc32("n-$node-{$t->timestamp}") % 10000) / 10000;
        $sun = $hour > 6 && $hour < 18 ? sin(M_PI * ($hour - 6) / 12) : 0;
        $s = $sun * $cloud * (0.9 + 0.2 * $noise);

        $voltage = 12.1 + 1.9 * $s + ($noise - 0.5) * 0.1;
        $current = 0.08 + 4.2 * $s + ($noise - 0.5) * 0.04;

        return [
            'temperature' => round(24 + 2 * sin(M_PI * ($hour - 9) / 12) + 16 * $s + ($noise - 0.5) * 0.6, 1),
            'voltage' => round($voltage, 2),
            'current' => round($current, 2),
            'power' => round($voltage * $current, 1),
            'irradiance' => (int) round(1000 * $s),
        ];
    }

    /** Rata-rata harian 7 hari & rata-rata per 30 menit 24 jam untuk tiap sensor. */
    private function trend(array $devices, CarbonImmutable $now): array
    {
        $days = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        $active = array_filter($devices, fn ($d) => $d['aktif']);
        $out = [];

        $avgAt = function (CarbonImmutable $t) use ($active) {
            $rows = [];
            foreach ($active as $d) {
                if ($t <= $d['last_seen']) {
                    $rows[] = $this->sample($d['id'], $t);
                }
            }

            return $rows;
        };

        // 24 jam: 48 bucket @30 menit
        $h24 = [];
        $start = $now->subDay()->startOfHour();
        for ($i = 0; $i < 48; $i++) {
            $t = $start->addMinutes(30 * $i);
            $h24[] = ['label' => $t->format('H:i'), 'title' => $t->translatedFormat('d M H:i'), 'rows' => $avgAt($t)];
        }

        // 7 hari: rata-rata sampel tiap 30 menit per hari
        $d7 = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = $now->subDays($i)->startOfDay();
            $rows = [];
            for ($m = 0; $m < 48; $m++) {
                $t = $day->addMinutes(30 * $m);
                if ($t > $now) {
                    break;
                }
                array_push($rows, ...$avgAt($t));
            }
            $d7[] = ['label' => $days[$day->dayOfWeek], 'title' => $days[$day->dayOfWeek].', '.$day->format('d M'), 'rows' => $rows];
        }

        foreach (array_keys(self::SENSORS) as $key) {
            $mean = fn ($rows) => count($rows) ? round(array_sum(array_column($rows, $key)) / count($rows), 2) : null;
            $out[$key] = [
                '24h' => array_map(fn ($b) => ['label' => $b['label'], 'title' => $b['title'], 'value' => $mean($b['rows'])], $h24),
                '7d' => array_map(fn ($b) => ['label' => $b['label'], 'title' => $b['title'], 'value' => $mean($b['rows'])], $d7),
            ];
        }

        return $out;
    }

    public static function mockDevices(CarbonImmutable $now): array
    {
        $slot = $now->setTime($now->hour, intdiv($now->minute, 5) * 5);

        return [
            ['id' => 1, 'node_id' => 1, 'nama' => 'Node 1 — Atap Gedung A', 'lokasi' => 'Gedung A, Atap', 'aktif' => true, 'last_seen' => $slot, 'packet_loss' => 1.0, 'rssi' => -72, 'snr' => 9.5],
            ['id' => 2, 'node_id' => 2, 'nama' => 'Node 2 — Atap Gedung B', 'lokasi' => 'Gedung B, Atap', 'aktif' => true, 'last_seen' => $slot, 'packet_loss' => 6.0, 'rssi' => -88, 'snr' => 6.0],
            ['id' => 3, 'node_id' => 3, 'nama' => 'Node 3 — Area Parkir', 'lokasi' => 'Area Parkir Timur', 'aktif' => true, 'last_seen' => $slot->subMinutes(130), 'packet_loss' => 4.0, 'rssi' => -103, 'snr' => 1.5],
        ];
    }

    public static function mockGateway(CarbonImmutable $now): array
    {
        return [
            'id' => 1,
            'nama' => 'Gateway LoRa Utama',
            'lokasi' => 'Ruang Kontrol, Gedung A',
            'status' => 'online',
            'mqtt_status' => 'connected',
            'internet_status' => 'connected',
            'last_seen' => $now->subMinute(),
            'beacon_menit' => 5,
            'broker' => 'mqtt.omahiot.example:8883',
            'topic' => 'omahiot/gateway-1/node/+/data',
            'ip_lokal' => '192.168.1.50',
        ];
    }

    /** Tambahkan status online/offline/nonaktif berdasarkan last_seen. */
    public static function withStatus(array $devices, CarbonImmutable $now, int $offlineMinutes = 15): array
    {
        return array_map(function ($d) use ($now, $offlineMinutes) {
            $d['status'] = ! $d['aktif'] ? 'nonaktif'
                : ($d['last_seen']->diffInMinutes($now, true) > $offlineMinutes ? 'offline' : 'online');

            return $d;
        }, $devices);
    }

    public static function mockAlerts(CarbonImmutable $now): array
    {
        return [
            ['id' => 1, 'device' => 'Node 3 — Area Parkir', 'text' => 'Perangkat offline — tidak mengirim data', 'waktu' => $now->subMinutes(115), 'status' => 'baru'],
            ['id' => 2, 'device' => 'Node 2 — Atap Gedung B', 'text' => 'Suhu 41,3 °C di atas ambang 40,0 °C', 'waktu' => $now->subMinutes(40), 'status' => 'baru'],
            ['id' => 3, 'device' => 'Node 1 — Atap Gedung A', 'text' => 'Tegangan 10,30 V di bawah ambang 10,50 V', 'waktu' => $now->subHours(5), 'status' => 'ditangani'],
            ['id' => 4, 'device' => 'Node 3 — Area Parkir', 'text' => 'Irradiance 1.236 W/m² di atas ambang 1.200 W/m²', 'waktu' => $now->subHours(26), 'status' => 'ditangani'],
            ['id' => 5, 'device' => 'Node 2 — Atap Gedung B', 'text' => 'Daya 84,6 W di atas ambang 80,0 W', 'waktu' => $now->subDays(2), 'status' => 'selesai'],
        ];
    }
}
