<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Measurement;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Data historis & laporan (PRD 4.3).
 *
 * Filter (perangkat, jenis sensor, rentang tanggal) dibaca dari query string sehingga
 * bisa dibagikan lewat URL dan dipakai ulang oleh ekspor CSV dan data grafik.
 * Tanggal filter diartikan dalam zona waktu dashboard (WIB).
 */
class HistoricalDataController extends Controller
{
    public const SENSORS = [
        'temperature' => 'Temperature (°C)',
        'voltage' => 'Voltage (V)',
        'current' => 'Current (A)',
        'power' => 'Power (W)',
        'irradiance' => 'Irradiance (W/m²)',
    ];

    /** Kolom ekspor CSV disusun mengikuti format kartu SD Gateway (PRD 5.3). */
    private const CSV_HEADER = [
        'Timestamp',
        'Packet Number',
        'Node ID',
        'Device Name',
        'Temperature (°C)',
        'Voltage (V)',
        'Current (A)',
        'Power (W)',
        'Irradiance (W/m²)',
        'RSSI (dBm)',
        'SNR (dB)',
        'Packet Loss (%)',
    ];

    private const PER_PAGE = 10;

    /** Titik per node: 10 pembacaan terakhir, atau 10 interval bila tanggal difilter. */
    private const CHART_MAX_POINTS = 10;

    /** Batas selisih waktu pembacaan antar node yang masih dianggap satu putaran kirim. */
    private const ROUND_WINDOW_SECONDS = 300;

    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);
        $query = $this->filteredQuery($filters);
        $page = $this->paginateRows($query);

        return view('dashboard.historical.index', [
            'devices' => $this->deviceOptions(),
            'sensors' => self::SENSORS,
            'data' => $page->getCollection()->map(fn (Measurement $m) => $this->toRow($m))->all(),
            'paginator' => $page,
            'stats' => $this->stats($query),
            'filters' => $filters,
        ]);
    }

    /** Hasil filter dalam bentuk JSON (halaman per 25 baris). */
    public function filter(Request $request): JsonResponse
    {
        $filters = $this->validatedFilters($request);
        $page = $this->paginateRows($this->filteredQuery($filters));

        return response()->json([
            'data' => $page->getCollection()->map(fn (Measurement $m) => $this->toRow($m))->values(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    /** Ekspor semua baris yang sesuai filter, urut waktu naik. */
    public function exportCsv(Request $request): StreamedResponse
    {
        $query = $this->filteredQuery($this->validatedFilters($request))
            ->with('device:id,name,node_id')
            ->orderBy('recorded_at');

        $filename = 'sensor_data_'.CarbonImmutable::now(DashboardController::tz())->format('Y-m-d_H-i-s').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8 dengan benar
            fputcsv($out, self::CSV_HEADER);

            foreach ($query->lazy(500) as $m) {
                fputcsv($out, [
                    $this->localTimestamp($m),
                    $m->packet_no,
                    $m->device?->node_id,
                    $m->device?->name,
                    $this->num($m->temperature),
                    $this->num($m->voltage),
                    $this->num($m->current),
                    $this->num($m->power),
                    $this->num($m->irradiance),
                    $m->rssi,
                    $this->num($m->snr),
                    $this->num($m->packet_loss),
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Data grafik: satu seri per node untuk tiap sensor, pada sumbu waktu yang sama.
     *
     * - Tanpa filter tanggal: 10 putaran kirim terakhir; pembacaan di putaran yang sama ditaruh
     *   di waktu yang sama.
     * Node tanpa data di rentang yang ditampilkan tidak dikirim sebagai seri.
     * - Dengan filter tanggal: rentang dibagi 10 interval sama panjang; tiap node dirata-rata
     *   per interval sehingga titik ketiga node sejajar di waktu yang sama.
     *
     * Titik dikirim sebagai {x: epoch ms, y: nilai}. `style` adalah posisi node dalam daftar
     * semua perangkat, sehingga warna & pola garis node tetap sama walau perangkat difilter.
     * Jenis sensor memilih seri yang dikembalikan; kosong berarti semua sensor.
     */
    public function getChartData(Request $request): JsonResponse
    {
        $filters = $this->validatedFilters($request);
        $sensorKeys = $filters['sensor_type'] ? [$filters['sensor_type']] : array_keys(self::SENSORS);

        $devices = Device::orderBy('node_id')->orderBy('id')
            ->get(['id', 'name', 'node_id'])
            ->values()
            ->map(fn (Device $d, int $i) => ['id' => $d->id, 'name' => $d->name, 'node_id' => $d->node_id, 'style' => $i])
            ->filter(fn (array $d) => $filters['device_id'] === null || $d['id'] === $filters['device_id'])
            ->values();

        $latestMode = $filters['date_from'] === null && $filters['date_to'] === null;
        [$points, $meta] = $latestMode
            ? $this->latestChartPoints($devices)
            : $this->rangeChartPoints($filters);

        $datasets = [];
        foreach ($sensorKeys as $key) {
            foreach ($devices as $device) {
                if (! isset($points[$device['id']])) {
                    continue; // node tidak mengirim data di rentang ini
                }
                $datasets[] = [
                    'sensor' => $key,
                    'sensor_label' => self::SENSORS[$key],
                    'device_id' => $device['id'],
                    'label' => $device['name'],
                    'node_id' => $device['node_id'],
                    'style' => $device['style'],
                    'data' => array_map(
                        fn (array $p) => ['x' => $p['x'], 'y' => $p[$key]],
                        $points[$device['id']] ?? []
                    ),
                ];
            }
        }

        return response()->json([
            'datasets' => $datasets,
            'meta' => $meta + [
                'mode' => $latestMode ? 'latest' : 'range',
                'timezone' => DashboardController::tz(),
                'limit' => self::CHART_MAX_POINTS,
            ],
        ]);
    }

    /**
     * 10 putaran kirim terakhir (keseluruhan, bukan per node); node yang tidak mengirim di rentang itu
     * tidak ikut. Node mengirim pada siklus yang sama tetapi selisih beberapa detik, jadi pembacaan
     * dikelompokkan ke satu putaran agar titik semua node sejajar di waktu yang sama.
     * Putaran baru dimulai bila node yang sama mengirim lagi, atau selisih dari awal putaran
     * melebihi ROUND_WINDOW_SECONDS. Waktu putaran = pembacaan paling awal di putaran itu.
     *
     * @return array{0: array<int, list<array>>, 1: array}
     */
    private function latestChartPoints(Collection $devices): array
    {
        $readings = [];
        foreach ($devices as $device) {
            $rows = Measurement::where('device_id', $device['id'])
                ->orderByDesc('recorded_at')->orderByDesc('id')
                ->limit(self::CHART_MAX_POINTS)
                ->get(['device_id', 'recorded_at', ...array_keys(self::SENSORS)]);
            array_push($readings, ...$rows->all());
        }
        usort($readings, fn (Measurement $a, Measurement $b) => $a->recorded_at->getTimestampMs() <=> $b->recorded_at->getTimestampMs());

        $rounds = [];
        foreach ($readings as $m) {
            $ts = $m->recorded_at->getTimestamp();
            $last = array_key_last($rounds);
            if ($last === null
                || isset($rounds[$last]['nodes'][$m->device_id])
                || $ts - $rounds[$last]['start'] > self::ROUND_WINDOW_SECONDS) {
                $rounds[] = ['start' => $ts, 'x' => $m->recorded_at->getTimestampMs(), 'nodes' => []];
                $last = array_key_last($rounds);
            }
            $rounds[$last]['nodes'][$m->device_id] = $this->sensorValues($m);
        }
        $rounds = array_slice($rounds, -self::CHART_MAX_POINTS);

        // Hanya node yang mengirim di 10 putaran terakhir; node lain tidak ditampilkan
        $sending = []; // dikunci device_id
        foreach ($rounds as $round) {
            $sending += $round['nodes'];
        }

        $empty = array_fill_keys(array_keys(self::SENSORS), null);
        $points = [];
        foreach ($devices as $device) {
            if (! isset($sending[$device['id']])) {
                continue;
            }
            $points[$device['id']] = array_map(
                fn (array $round) => ['x' => $round['x']] + ($round['nodes'][$device['id']] ?? $empty),
                $rounds
            );
        }

        return [$points, ['granularity' => 'minute', 'ticks' => array_column($rounds, 'x')]];
    }

    /**
     * Bagi rentang filter menjadi 10 interval sama panjang, rata-rata per node per interval.
     * Interval pertama [awal, akhir1], berikutnya (akhir sebelumnya, akhir].
     *
     * @return array{0: array<int, list<array>>, 1: array}
     */
    private function rangeChartPoints(array $filters): array
    {
        $query = $this->filteredQuery($filters);
        $range = (clone $query)->toBase()
            ->selectRaw('MIN(recorded_at) as first_at, MAX(recorded_at) as last_at')
            ->first();

        if ($range->first_at === null) {
            return [[], ['granularity' => 'minute', 'ticks' => []]];
        }

        $first = $this->fromUtc($range->first_at);
        $last = $this->fromUtc($range->last_at);

        $start = $filters['date_from'] !== null
            ? $this->localFilterTime($filters['date_from'])
            : $first->startOfDay();

        $end = $filters['date_to'] !== null
            ? $this->localFilterTime($filters['date_to'], true)
            : ($filters['date_from'] !== null ? $last : $last->endOfDay());

        $spanSeconds = max(1, $start->diffInSeconds($end));
        $intervalSeconds = max(1, (int) ceil($spanSeconds / self::CHART_MAX_POINTS));

        $bucketEnds = [];
        for ($i = 0; $i < self::CHART_MAX_POINTS; $i++) {
            $bucketEnd = $i === self::CHART_MAX_POINTS - 1 ? $end : $start->addSeconds(($i + 1) * $intervalSeconds);
            $bucketEnds[] = $bucketEnd->min($end)->getTimestampMs();
        }

        // Akumulasi per node per interval tanpa memuat semua baris sekaligus
        $sums = [];
        $counts = [];
        $startTs = $start->getTimestamp();
        $rows = (clone $query)->select(['id', 'device_id', 'recorded_at', ...array_keys(self::SENSORS)])->lazyById(1000);
        foreach ($rows as $m) {
            $diff = $m->recorded_at->getTimestamp() - $startTs;
            $bucket = $diff <= 0 ? 0 : min(self::CHART_MAX_POINTS - 1, (int) ceil($diff / $intervalSeconds) - 1);

            foreach (array_keys(self::SENSORS) as $sensor) {
                if ($m->{$sensor} !== null) {
                    $sums[$m->device_id][$bucket][$sensor] = ($sums[$m->device_id][$bucket][$sensor] ?? 0) + (float) $m->{$sensor};
                    $counts[$m->device_id][$bucket][$sensor] = ($counts[$m->device_id][$bucket][$sensor] ?? 0) + 1;
                }
            }
        }

        $points = [];
        foreach (array_keys($counts) as $deviceId) {
            foreach ($bucketEnds as $bucket => $x) {
                $point = ['x' => $x];
                foreach (array_keys(self::SENSORS) as $sensor) {
                    $n = $counts[$deviceId][$bucket][$sensor] ?? 0;
                    $point[$sensor] = $n > 0 ? round($sums[$deviceId][$bucket][$sensor] / $n, 2) : null;
                }
                $points[$deviceId][] = $point;
            }
        }

        return [$points, ['granularity' => $this->chartGranularity($intervalSeconds), 'ticks' => $bucketEnds]];
    }

    /** @return array<string, ?float> nilai tiap sensor, dibulatkan 2 desimal */
    private function sensorValues(Measurement $m): array
    {
        $values = [];
        foreach (array_keys(self::SENSORS) as $sensor) {
            $values[$sensor] = $m->{$sensor} !== null ? round((float) $m->{$sensor}, 2) : null;
        }

        return $values;
    }

    /** Ketelitian label sumbu waktu sesuai lebar interval. */
    private function chartGranularity(int $intervalSeconds): string
    {
        if ($intervalSeconds < 24 * 3600) {
            return 'minute';
        }

        return $intervalSeconds <= 30 * 86400 ? 'day' : 'month';
    }

    /** @return array{device_id: ?int, sensor_type: ?string, date_from: ?string, date_to: ?string} */
    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'device_id' => ['nullable', 'integer', 'exists:devices,id'],
            'sensor_type' => ['nullable', 'string', Rule::in(array_keys(self::SENSORS))],
            'date_from' => ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2})?$/'],
            'date_to' => ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2})?$/', 'after_or_equal:date_from'],
        ]);

        return [
            'device_id' => isset($validated['device_id']) ? (int) $validated['device_id'] : null,
            'sensor_type' => $validated['sensor_type'] ?? null,
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
        ];
    }

    /** Query dasar yang dipakai bersama oleh tabel, statistik, grafik, dan ekspor. */
    private function filteredQuery(array $filters): Builder
    {
        $query = Measurement::query();

        if ($filters['device_id'] !== null) {
            $query->where('device_id', $filters['device_id']);
        }

        // recorded_at disimpan dalam UTC; filter datetime diparsing dalam zona tampilan.
        if ($filters['date_from'] !== null) {
            $query->where('recorded_at', '>=', $this->localFilterTime($filters['date_from'])->utc()->format('Y-m-d H:i:s'));
        }

        if ($filters['date_to'] !== null) {
            $query->where('recorded_at', '<=', $this->localFilterTime($filters['date_to'], true)->utc()->format('Y-m-d H:i:s'));
        }

        return $query;
    }

    private function paginateRows(Builder $query): LengthAwarePaginator
    {
        return (clone $query)
            ->with('device:id,name,node_id')
            ->orderByDesc('recorded_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /** Ringkasan untuk kartu statistik di bawah tabel. */
    private function stats(Builder $query): array
    {
        $row = (clone $query)->toBase()
            ->selectRaw('COUNT(*) as total, MIN(recorded_at) as first_at, MAX(recorded_at) as last_at, AVG(packet_loss) as avg_loss')
            ->first();

        return [
            'total' => (int) $row->total,
            'from' => $row->first_at ? $this->fromUtc($row->first_at) : null,
            'to' => $row->last_at ? $this->fromUtc($row->last_at) : null,
            'avg_packet_loss' => $row->avg_loss !== null ? round((float) $row->avg_loss, 2) : null,
        ];
    }

    /** @return array<int, array{id: int, name: string}> */
    private function deviceOptions(): array
    {
        return Device::orderBy('node_id')
            ->get(['id', 'name'])
            ->map(fn (Device $d) => ['id' => $d->id, 'name' => $d->name])
            ->all();
    }

    /** Baris tabel; kunci array mengikuti yang dipakai view historical/index. */
    private function toRow(Measurement $m): array
    {
        return [
            'timestamp' => $this->localTimestamp($m),
            'packet_number' => $m->packet_no,
            'device_id' => $m->device_id,
            'device_name' => $m->device?->name ?? '-',
            'node_id' => $m->device?->node_id,
            'temperature' => $this->num($m->temperature),
            'voltage' => $this->num($m->voltage),
            'current' => $this->num($m->current),
            'power' => $this->num($m->power),
            'irradiance' => $this->num($m->irradiance),
            'rssi' => $m->rssi,
            'snr' => $this->num($m->snr),
            'packet_loss' => $this->num($m->packet_loss),
        ];
    }

    /** Nilai sensor sebagai float; null bila tidak dikirim pada paket. */
    private function num(mixed $value): ?float
    {
        return $value === null ? null : (float) $value;
    }

    private function localTimestamp(Measurement $m): string
    {
        return $m->recorded_at->timezone(DashboardController::tz())->format('Y-m-d H:i:s');
    }

    /** Konversi nilai datetime-local zona tampilan ke UTC untuk query database. */
    private function localFilterTime(string $value, bool $endOfDay = false): CarbonImmutable
    {
        $format = str_contains($value, 'T') ? 'Y-m-d\TH:i' : 'Y-m-d';
        $date = CarbonImmutable::createFromFormat($format, $value, DashboardController::tz());

        if (! str_contains($value, 'T')) {
            $date = $endOfDay ? $date->endOfDay() : $date->startOfDay();
        }

        return $date;
    }

    private function fromUtc(string $value): CarbonImmutable
    {
        return CarbonImmutable::parse($value, 'UTC')->timezone(DashboardController::tz());
    }
}
