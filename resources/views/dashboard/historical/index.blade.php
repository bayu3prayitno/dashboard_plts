@extends('layouts.app')

@section('title', 'Data Historis - IoT Dashboard')
@section('page-title', 'Data Historis & Laporan')
@section('page-subtitle', 'Lihat, analisis, dan export data sensor historis')

@section('content')
<div class="space-y-6">
    <!-- Filter Section -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Filter Data</h3>

        <form method="GET" action="{{ route('historical.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Device Filter -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Perangkat</label>
                    <select name="device_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Perangkat</option>
                        @foreach($devices as $device)
                            <option value="{{ $device['id'] }}" @selected((string) $filters['device_id'] === (string) $device['id'])>{{ $device['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Sensor Type Filter -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Jenis Sensor</label>
                    <select name="sensor_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Sensor</option>
                        @foreach($sensors as $key => $label)
                            <option value="{{ $key }}" @selected($filters['sensor_type'] === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Date From -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Dari Tanggal</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Date To -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Sampai Tanggal</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            @if($errors->any())
                <p class="text-sm text-red-600">{{ $errors->first() }}</p>
            @endif

            <div class="flex gap-3">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Terapkan Filter</button>
                <a href="{{ route('historical.exportCsv', request()->query()) }}" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Export CSV
                </a>
                <a href="{{ route('historical.index') }}" class="bg-gray-300 text-gray-800 px-6 py-2 rounded-lg hover:bg-gray-400 flex items-center">Reset</a>
            </div>
        </form>
    </div>

    <!-- Historical Data Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200 sticky top-0">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Timestamp</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Packet #</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Perangkat</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-700">Suhu (°C)</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-700">Tegangan (V)</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-700">Arus (A)</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-700">Daya (W)</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-700">Irradiance (W/m²)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($data as $row)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-800">{{ $row['timestamp'] }}</td>
                        <td class="px-4 py-3 font-mono text-gray-600">{{ $row['packet_number'] }}</td>
                        <td class="px-4 py-3 text-gray-800">{{ $row['device_name'] }}</td>
                        <td class="px-4 py-3 text-center text-gray-800">{{ $row['temperature'] }}</td>
                        <td class="px-4 py-3 text-center text-gray-800">{{ $row['voltage'] }}</td>
                        <td class="px-4 py-3 text-center text-gray-800">{{ $row['current'] }}</td>
                        <td class="px-4 py-3 text-center text-gray-800">{{ $row['power'] }}</td>
                        <td class="px-4 py-3 text-center text-gray-800">{{ $row['irradiance'] }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">Tidak ada data yang ditemukan</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($paginator->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">{{ $paginator->links() }}</div>
        @endif
    </div>

    <!-- Chart Visualization (data dari endpoint chart-data, mengikuti filter di atas) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Grafik Suhu</h3>
            <div class="relative h-64"><canvas id="chart-temperature" aria-label="Grafik suhu"></canvas></div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Grafik Tegangan & Arus</h3>
            <div class="relative h-64"><canvas id="chart-voltage-current" aria-label="Grafik tegangan dan arus"></canvas></div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Grafik Daya</h3>
            <div class="relative h-64"><canvas id="chart-power" aria-label="Grafik daya"></canvas></div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Grafik Irradiance</h3>
            <div class="relative h-64"><canvas id="chart-irradiance" aria-label="Grafik irradiance"></canvas></div>
        </div>
    </div>

    <!-- Data Statistics -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Statistik Data</h3>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="p-4 bg-gray-50 rounded">
                <p class="text-gray-600 text-sm">Total Records</p>
                <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['total'], 0, ',', '.') }}</p>
            </div>
            <div class="p-4 bg-gray-50 rounded">
                <p class="text-gray-600 text-sm">Periode Data</p>
                <p class="text-sm font-bold text-gray-800">{{ $stats['from'] ? $stats['from']->format('d/m/Y H:i') . ' - ' . $stats['to']->format('d/m/Y H:i') : '-' }}</p>
            </div>
            <div class="p-4 bg-gray-50 rounded">
                <p class="text-gray-600 text-sm">Penerimaan Paket</p>
                <p class="text-2xl font-bold text-green-600">{{ $stats['avg_packet_loss'] !== null ? number_format(100 - $stats['avg_packet_loss'], 1, ',', '.') : '-' }}%</p>
            </div>
            <div class="p-4 bg-gray-50 rounded">
                <p class="text-gray-600 text-sm">Rata-rata Packet Loss</p>
                <p class="text-2xl font-bold text-gray-800">{{ $stats['avg_packet_loss'] !== null ? number_format($stats['avg_packet_loss'], 2, ',', '.') : '-' }}%</p>
            </div>
            <div class="p-4 bg-gray-50 rounded">
                <p class="text-gray-600 text-sm">Format Export</p>
                <p class="text-sm font-bold text-gray-800">CSV</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js"></script>
<script>
(function () {
    const URL_DATA = @json(route('historical.chartData')) + window.location.search;

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
        canvas.parentElement.innerHTML = '<div class="h-64 flex items-center justify-center text-gray-500 text-sm">Belum ada data untuk filter ini.</div>';
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
                    ...line(d.label, d.data, d.borderColor),
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
                c.parentElement.innerHTML = '<div class="h-64 flex items-center justify-center text-red-500 text-sm">Gagal memuat data grafik.</div>';
            });
        });
})();
</script>
@endpush
