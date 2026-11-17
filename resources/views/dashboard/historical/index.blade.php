@extends('layouts.app')

@section('title', 'Data Historis - IoT Dashboard')
@section('page-title', 'Data Historis & Laporan')
@section('page-subtitle', 'Lihat, analisis, dan export data sensor historis')

@section('content')
<div class="space-y-4">
    <!-- Toolbar: filter (corong) + export -->
    <div class="flex items-center justify-end gap-2">
        <button type="button" onclick="openFilterModal()" title="Filter" aria-label="Filter"
                class="btn btn-outline relative bg-white/60 px-3">
            <x-icon name="filter" :size="16" />
            Filter
            @if(($filters['device_id'] ?? '') !== '' || ($filters['date_from'] ?? '') !== '' || ($filters['date_to'] ?? '') !== '')
                <span class="absolute -top-1 -right-1 size-2.5 rounded-full bg-lime ring-2 ring-canvas" aria-hidden="true"></span>
            @endif
        </button>
        <a href="{{ route('historical.exportCsv', request()->query()) }}" class="btn btn-primary">
            <x-icon name="download" :size="16" />
            Ekspor CSV
        </a>
    </div>

    <!-- Modal Filter -->
    <div id="filter-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="filter-modal-title">
        <div class="absolute inset-0 bg-ink/40 backdrop-blur-[2px]" onclick="closeFilterModal()"></div>
        <div class="card relative w-full max-w-2xl p-6 shadow-xl">
            <div class="mb-5 flex items-center justify-between gap-3">
                <h3 id="filter-modal-title" class="flex items-center gap-2 text-lg font-semibold">
                    <x-icon name="filter" :size="18" class="text-muted" />
                    Filter Data
                </h3>
                <button type="button" onclick="closeFilterModal()" aria-label="Tutup" class="grid size-9 place-items-center rounded-full text-muted transition-colors hover:bg-canvas hover:text-ink">
                    <x-icon name="x" :size="18" />
                </button>
            </div>

            <form method="GET" action="{{ route('historical.index') }}" class="space-y-5">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <!-- Device Filter -->
                    <div class="min-w-0">
                        <label for="device_id" class="mb-1.5 block text-xs font-medium text-muted">Perangkat</label>
                        <select id="device_id" name="device_id"
                                class="w-full truncate rounded-full border border-line bg-white px-4 py-2.5 text-sm text-ink outline-none focus:border-primary focus:ring-2 focus:ring-primary/15">
                            <option value="">Semua Perangkat</option>
                            @foreach($devices as $device)
                                <option value="{{ $device['id'] }}" @selected((string) $filters['device_id'] === (string) $device['id'])>{{ $device['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date From -->
                    <div>
                        <label for="date_from" class="mb-1.5 block text-xs font-medium text-muted">Dari Tanggal</label>
                        <input id="date_from" type="date" name="date_from" value="{{ $filters['date_from'] }}"
                               class="w-full rounded-full border border-line bg-white px-4 py-2.5 text-sm text-ink outline-none focus:border-primary focus:ring-2 focus:ring-primary/15">
                    </div>

                    <!-- Date To -->
                    <div>
                        <label for="date_to" class="mb-1.5 block text-xs font-medium text-muted">Sampai Tanggal</label>
                        <input id="date_to" type="date" name="date_to" value="{{ $filters['date_to'] }}"
                               class="w-full rounded-full border border-line bg-white px-4 py-2.5 text-sm text-ink outline-none focus:border-primary focus:ring-2 focus:ring-primary/15">
                    </div>
                </div>

                @if($errors->any())
                    <p class="text-sm text-red-600">{{ $errors->first() }}</p>
                @endif

                <div class="flex flex-wrap justify-end gap-2 border-t border-line pt-4">
                    <button type="button" onclick="closeFilterModal()" class="btn btn-outline">Batal</button>
                    <a href="{{ route('historical.index') }}" class="btn text-muted hover:bg-canvas hover:text-ink">Reset</a>
                    <button type="submit" class="btn btn-primary">Terapkan Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Data Historis -->
    <section class="card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead>
                    <tr class="border-b border-line text-xs text-muted">
                        <th class="px-4 py-3 font-medium whitespace-nowrap">Timestamp</th>
                        <th class="px-4 py-3 font-medium whitespace-nowrap">Packet #</th>
                        <th class="px-4 py-3 font-medium whitespace-nowrap">Perangkat</th>
                        <th class="px-4 py-3 text-center font-medium whitespace-nowrap">Suhu (°C)</th>
                        <th class="px-4 py-3 text-center font-medium whitespace-nowrap">Tegangan (V)</th>
                        <th class="px-4 py-3 text-center font-medium whitespace-nowrap">Arus (A)</th>
                        <th class="px-4 py-3 text-center font-medium whitespace-nowrap">Daya (W)</th>
                        <th class="px-4 py-3 text-center font-medium whitespace-nowrap">Irradiance (W/m²)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($data as $row)
                    <tr class="transition-colors hover:bg-canvas/60">
                        <td class="px-4 py-3 whitespace-nowrap">{{ $row['timestamp'] }}</td>
                        <td class="px-4 py-3 font-mono text-muted">{{ $row['packet_number'] }}</td>
                        <td class="px-4 py-3 font-medium">{{ $row['device_name'] }}</td>
                        <td class="px-4 py-3 text-center tabular-nums">{{ $row['temperature'] }}</td>
                        <td class="px-4 py-3 text-center tabular-nums">{{ $row['voltage'] }}</td>
                        <td class="px-4 py-3 text-center tabular-nums">{{ $row['current'] }}</td>
                        <td class="px-4 py-3 text-center tabular-nums">{{ $row['power'] }}</td>
                        <td class="px-4 py-3 text-center tabular-nums">{{ $row['irradiance'] }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-sm text-muted">Tidak ada data yang ditemukan</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($paginator->hasPages())
            <div class="border-t border-line px-4 py-3 text-sm text-muted">{{ $paginator->links() }}</div>
        @endif
    </section>

    <!-- Grafik (data dari endpoint chart-data, mengikuti filter di atas) -->
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <section class="card">
            <h2 class="mb-4 text-base font-semibold">Grafik Suhu</h2>
            <div class="relative h-64"><canvas id="chart-temperature" aria-label="Grafik suhu"></canvas></div>
        </section>
        <section class="card">
            <h2 class="mb-4 text-base font-semibold">Grafik Tegangan & Arus</h2>
            <div class="relative h-64"><canvas id="chart-voltage-current" aria-label="Grafik tegangan dan arus"></canvas></div>
        </section>
        <section class="card">
            <h2 class="mb-4 text-base font-semibold">Grafik Daya</h2>
            <div class="relative h-64"><canvas id="chart-power" aria-label="Grafik daya"></canvas></div>
        </section>
        <section class="card">
            <h2 class="mb-4 text-base font-semibold">Grafik Irradiance</h2>
            <div class="relative h-64"><canvas id="chart-irradiance" aria-label="Grafik irradiance"></canvas></div>
        </section>
    </div>

    <!-- Statistik Data -->
    <section class="card">
        <h2 class="mb-4 text-base font-semibold">Statistik Data</h2>
        <div class="grid grid-cols-2 gap-3 md:grid-cols-5">
            <div class="rounded-2xl bg-canvas p-4">
                <p class="text-xs text-muted">Total Records</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums">{{ number_format($stats['total'], 0, ',', '.') }}</p>
            </div>
            <div class="rounded-2xl bg-canvas p-4">
                <p class="text-xs text-muted">Periode Data</p>
                <p class="mt-1 text-sm font-semibold">{{ $stats['from'] ? $stats['from']->format('d/m/Y H:i') . ' - ' . $stats['to']->format('d/m/Y H:i') : '-' }}</p>
            </div>
            <div class="rounded-2xl bg-canvas p-4">
                <p class="text-xs text-muted">Penerimaan Paket</p>
                <p class="mt-1 text-2xl font-semibold text-lime-dark tabular-nums">{{ $stats['avg_packet_loss'] !== null ? number_format(100 - $stats['avg_packet_loss'], 1, ',', '.') : '-' }}%</p>
            </div>
            <div class="rounded-2xl bg-canvas p-4">
                <p class="text-xs text-muted">Rata-rata Packet Loss</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums">{{ $stats['avg_packet_loss'] !== null ? number_format($stats['avg_packet_loss'], 2, ',', '.') : '-' }}%</p>
            </div>
            <div class="rounded-2xl bg-canvas p-4">
                <p class="text-xs text-muted">Format Ekspor</p>
                <p class="mt-1 text-sm font-semibold">CSV</p>
            </div>
        </div>
    </section>
</div>

<script>
    function openFilterModal() {
        document.getElementById('filter-modal').classList.remove('hidden');
        document.getElementById('filter-modal').classList.add('flex');
    }
    function closeFilterModal() {
        document.getElementById('filter-modal').classList.add('hidden');
        document.getElementById('filter-modal').classList.remove('flex');
    }
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeFilterModal();
    });
    @if($errors->any())
        document.addEventListener('DOMContentLoaded', openFilterModal);
    @endif
</script>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js"></script>
<script>
(function () {
    const URL_DATA = @json(route('historical.chartData')) + window.location.search;

    // Palet grafik: warna berbeda jelas satu sama lain, tetap selaras dengan tema hijau limau
    const COLORS = {
        temperature: '#d9730d', // oranye hangat
        voltage: '#6f9f12',     // hijau limau gelap (tema)
        current: '#0f766e',     // teal
        power: '#262a26',       // arang (tema)
        irradiance: '#b45309',  // kuning tanah
    };

    if (window.Chart) {
        Chart.defaults.color = '#6f766f';
        Chart.defaults.borderColor = '#e4e7e2';
        Chart.defaults.font.family = "'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif";
    }

    const line = (label, data, color) => ({
        label, data, borderColor: color, backgroundColor: color + '22',
        borderWidth: 2, pointRadius: 2, pointHoverRadius: 4, tension: 0.3, spanGaps: true,
    });

    const options = (yTitles) => {
        const scales = {
            x: { ticks: { maxTicksLimit: 10, autoSkip: true, maxRotation: 0 }, grid: { display: false } },
            y: { title: { display: true, text: yTitles[0] } },
        };
        if (yTitles[1]) scales.y1 = { position: 'right', title: { display: true, text: yTitles[1] }, grid: { drawOnChartArea: false } };
        return {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales, plugins: { legend: { position: 'bottom' } },
        };
    };

    const showEmpty = (id) => {
        const canvas = document.getElementById(id);
        canvas.parentElement.innerHTML = '<div class="flex h-64 items-center justify-center text-sm text-muted">Belum ada data untuk filter ini.</div>';
    };

    // Satu chart per kelompok sensor; yAxis menentukan sumbu untuk tiap seri
    const build = (id, labels, datasets, sensors, yTitles) => {
        const picked = datasets.filter((d) => sensors.includes(d.sensor));
        const hasData = picked.some((d) => d.data.some((v) => v !== null));
        if (!hasData) return showEmpty(id);
        new Chart(document.getElementById(id), {
            type: 'line',
            data: {
                labels,
                datasets: picked.map((d, i) => ({
                    ...line(d.label, d.data, COLORS[d.sensor] ?? d.borderColor),
                    yAxisID: i === 0 ? 'y' : 'y1',
                })),
            },
            options: options(yTitles),
        });
    };

    fetch(URL_DATA, { headers: { Accept: 'application/json' } })
        .then((r) => r.json())
        .then((json) => {
            const { labels, datasets } = json;
            build('chart-temperature', labels, datasets, ['temperature'], ['°C']);
            build('chart-voltage-current', labels, datasets, ['voltage', 'current'], ['V', 'A']);
            build('chart-power', labels, datasets, ['power'], ['W']);
            build('chart-irradiance', labels, datasets, ['irradiance'], ['W/m²']);
        })
        .catch(() => {
            ['chart-temperature', 'chart-voltage-current', 'chart-power', 'chart-irradiance'].forEach((id) => {
                const c = document.getElementById(id);
                c.parentElement.innerHTML = '<div class="flex h-64 items-center justify-center text-sm text-red-600">Gagal memuat data grafik.</div>';
            });
        });
})();
</script>
@endpush
