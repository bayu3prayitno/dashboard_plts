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

    private const SENSOR_COLORS = [
        'temperature' => '#ef4444',
        'voltage' => '#3b82f6',
        'current' => '#f59e0b',
        'power' => '#10b981',
        'irradiance' => '#8b5cf6',
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

    /** Maksimal sumber data yang dibaca sebelum dikumulatifkan menjadi grafik. */
    private const CHART_SOURCE_LIMIT = 10000;
    /** Maksimal titik grafik setelah data dikumulatifkan. */
    private const CHART_MAX_POINTS = 10;

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
     * Data grafik: rata-rata per waktu rekam (lintas perangkat bila perangkat tidak difilter).
     * Jenis sensor memilih seri yang dikembalikan; kosong berarti semua sensor.
     */
    public function getChartData(Request $request): JsonResponse
    {
        $filters = $this->validatedFilters($request);
        $query = $this->filteredQuery($filters)
            ->orderByDesc('recorded_at');

        $withoutDateFilter = $filters['date_from'] === null && $filters['date_to'] === null;
        if ($withoutDateFilter) {
            $query->limit(self::CHART_MAX_POINTS);
        }

        $measurements = $query
            ->get([
                'recorded_at',
                'temperature',
                'voltage',
                'current',
                'power',
                'irradiance',
            ])
            ->reverse()
            ->values();

        $rows = $filters['date_from'] === null && $filters['date_to'] === null
            ? $this->latestChartRows($measurements)
            : $this->aggregateChartRows($measurements, $filters);

        $sensorKeys = $filters['sensor_type'] ? [$filters['sensor_type']] : array_keys(self::SENSORS);

        return response()->json([
            'labels' => $rows->map(fn ($r) => $r->label)->all(),
            'datasets' => array_map(fn (string $key) => [
                'label' => self::SENSORS[$key],
                'sensor' => $key,
                'data' => $rows->map(fn ($r) => $r->{$key} !== null ? round((float) $r->{$key}, 2) : null)->all(),
                'borderColor' => self::SENSOR_COLORS[$key],
            ], $sensorKeys),
            'meta' => [
                'points' => $rows->count(),
                'limit' => self::CHART_MAX_POINTS,
            ],
        ]);
    }

    /** Ambil tepat 10 timestamp pengukuran terbaru tanpa mengelompokkan berdasarkan rentang. */
    private function latestChartRows(Collection $measurements): Collection
    {
        $timestamps = [];

        foreach ($measurements as $measurement) {
            $at = $measurement->recorded_at->timezone(DashboardController::tz());
            $key = $at->format('Y-m-d H:i:s');

            if (! isset($timestamps[$key])) {
                $timestamps[$key] = [
                    'recorded_at' => $key,
                    'label' => $at->format('d/m H:i'),
                    'sums' => [],
                    'counts' => [],
                ];
            }

            foreach (array_keys(self::SENSORS) as $sensor) {
                $value = $measurement->{$sensor};
                if ($value !== null) {
                    $timestamps[$key]['sums'][$sensor] = ($timestamps[$key]['sums'][$sensor] ?? 0) + (float) $value;
                    $timestamps[$key]['counts'][$sensor] = ($timestamps[$key]['counts'][$sensor] ?? 0) + 1;
                }
            }
        }

        $rows = [];
        foreach (array_slice($timestamps, -self::CHART_MAX_POINTS) as $timestamp) {
            $row = (object) [
                'recorded_at' => $timestamp['recorded_at'],
                'label' => $timestamp['label'],
            ];

            foreach (array_keys(self::SENSORS) as $sensor) {
                $count = $timestamp['counts'][$sensor] ?? 0;
                $row->{$sensor} = $count > 0
                    ? round($timestamp['sums'][$sensor] / $count, 2)
                    : null;
            }
            $rows[] = $row;
        }

        return collect($rows);
    }

    /** Bagi seluruh rentang filter menjadi maksimal 10 interval secara dinamis. */
    private function aggregateChartRows(Collection $measurements, array $filters): Collection
    {
        if ($measurements->isEmpty()) {
            return collect();
        }

        $tz = DashboardController::tz();
        $firstMeasurement = $measurements->first()->recorded_at->timezone($tz);
        $lastMeasurement = $measurements->last()->recorded_at->timezone($tz);

        $start = $filters['date_from'] !== null
            ? $this->localFilterTime($filters['date_from'])
            : $firstMeasurement->startOfDay();

        $end = $filters['date_to'] !== null
            ? $this->localFilterTime($filters['date_to'], true)
            : ($filters['date_from'] !== null ? $lastMeasurement : $lastMeasurement->endOfDay());

        $spanSeconds = max(1, $start->diffInSeconds($end));
        $intervalSeconds = max(1, (int) ceil($spanSeconds / self::CHART_MAX_POINTS));

        $rows = [];
        $previousEnd = $start;
        for ($index = 0; $index < self::CHART_MAX_POINTS; $index++) {
            $bucketEnd = $index === self::CHART_MAX_POINTS - 1
                ? $end
                : $start->addSeconds(($index + 1) * $intervalSeconds);

            $interval = $measurements->filter(function ($measurement) use ($index, $start, $previousEnd, $bucketEnd, $tz) {
                $at = $measurement->recorded_at->timezone($tz);

                if ($index === 0) {
                    return $at->gte($start) && $at->lte($bucketEnd);
                }

                return $at->gt($previousEnd) && $at->lte($bucketEnd);
            });

            $row = (object) [
                'recorded_at' => $bucketEnd->format('Y-m-d H:i:s'),
                'label' => $this->chartBucketLabel($bucketEnd, $intervalSeconds),
            ];

            foreach (array_keys(self::SENSORS) as $sensor) {
                $values = $interval
                    ->pluck($sensor)
                    ->filter(fn ($value) => $value !== null)
                    ->map(fn ($value) => (float) $value);

                $row->{$sensor} = $values->isNotEmpty()
                    ? round($values->avg(), 2)
                    : null;
            }
            $rows[] = $row;
            $previousEnd = $bucketEnd;
        }

        return collect($rows);
    }

    private function chartBucketLabel(CarbonImmutable $at, int $intervalSeconds): string
    {
        if ($intervalSeconds < 24 * 3600) {
            return $at->format('d/m H:i');
        }
        if ($intervalSeconds <= 30 * 86400) {
            return $at->format('d/m');
        }

        return $at->translatedFormat('M Y');
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
