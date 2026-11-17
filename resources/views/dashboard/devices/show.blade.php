@extends('layouts.app')

@section('title', 'Detail Perangkat - IoT Dashboard')
@section('page-title', $device->name)
@section('page-subtitle', 'Detail dan monitoring perangkat DEVICE_' . str_pad($device->node_id, 3, '0', STR_PAD_LEFT))

@section('content')
@php $status = $device->status(); @endphp
<div class="space-y-6">
    <!-- Device Information -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Device Info Card -->
        <div class="card">
            <h3 class="text-lg font-semibold text-ink mb-4">Informasi Perangkat</h3>
            <div class="space-y-3">
                <div>
                    <p class="text-muted text-sm">ID Node</p>
                    <p class="font-mono text-ink">DEVICE_{{ str_pad($device->node_id, 3, '0', STR_PAD_LEFT) }}</p>
                </div>
                <div>
                    <p class="text-muted text-sm">Nama</p>
                    <p class="text-ink">{{ $device->name }}</p>
                </div>
                <div>
                    <p class="text-muted text-sm">Lokasi</p>
                    <p class="text-ink">{{ $device->location }}</p>
                </div>
                <div>
                    <p class="text-muted text-sm">Gateway Terhubung</p>
                    <p class="text-ink">{{ $device->gateway?->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-muted text-sm">Status</p>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium mt-1
                        {{ $status === 'online' ? 'bg-primary-soft text-lime-dark' : ($status === 'offline' ? 'bg-red-100 text-red-800' : 'bg-canvas text-ink') }}">
                        {{ ucfirst($status) }}
                    </span>
                </div>
                <div>
                    <p class="text-muted text-sm">Update Terakhir</p>
                    <p class="text-ink">{{ $device->last_seen_at?->diffForHumans() ?? 'Belum ada data' }}</p>
                </div>
            </div>
            <div class="mt-6 pt-6 border-t border-line flex gap-2">
                <a href="{{ route('devices.edit', $device) }}" class="flex-1 btn btn-primary text-center">Edit</a>
                <button type="button" onclick="deleteResource(@js(route('devices.delete', $device)), { confirmText: 'Apakah Anda yakin ingin menghapus perangkat ini?', redirect: @js(route('devices.index')) })" class="flex-1 bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">Hapus</button>
            </div>
        </div>

        <!-- Current Sensor Values -->
        <div class="lg:col-span-2 card">
            <h3 class="text-lg font-semibold text-ink mb-4">Nilai Sensor Terkini</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="p-4 bg-red-50 rounded border border-red-200">
                    <p class="text-muted text-sm">Suhu</p>
                    <p class="text-3xl font-bold text-red-600">{{ $latest?->temperature ?? '-' }}°C</p>
                </div>
                <div class="p-4 bg-primary-soft rounded border border-lime/40">
                    <p class="text-muted text-sm">Tegangan</p>
                    <p class="text-3xl font-bold text-primary">{{ $latest?->voltage ?? '-' }}V</p>
                </div>
                <div class="p-4 bg-yellow-50 rounded border border-yellow-200">
                    <p class="text-muted text-sm">Arus</p>
                    <p class="text-3xl font-bold text-yellow-600">{{ $latest?->current ?? '-' }}A</p>
                </div>
                <div class="p-4 bg-purple-50 rounded border border-purple-200">
                    <p class="text-muted text-sm">Daya</p>
                    <p class="text-3xl font-bold text-purple-600">{{ $latest?->power ?? '-' }}W</p>
                </div>
                <div class="p-4 bg-indigo-50 rounded border border-indigo-200 col-span-2">
                    <p class="text-muted text-sm">Irradiance</p>
                    <p class="text-3xl font-bold text-indigo-600">{{ $latest?->irradiance ?? '-' }}W/m²</p>
                </div>
            </div>
            @if($latest)
                <p class="text-xs text-muted mt-4">Diterima {{ $latest->recorded_at->diffForHumans() }}</p>
            @endif
        </div>
    </div>

    <!-- Network Metrics -->
    <div class="card">
        <h3 class="text-lg font-semibold text-ink mb-4">Metrik Kesehatan Jaringan LoRa</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="p-4 bg-canvas/60 rounded">
                <div class="text-center">
                    <p class="text-muted text-sm mb-2">RSSI (Received Signal Strength)</p>
                    <p class="text-3xl font-bold text-ink">{{ $latest?->rssi ?? '-' }}</p>
                    <p class="text-xs text-muted mt-2">dBm</p>
                </div>
            </div>
            <div class="p-4 bg-canvas/60 rounded">
                <div class="text-center">
                    <p class="text-muted text-sm mb-2">SNR (Signal-to-Noise Ratio)</p>
                    <p class="text-3xl font-bold text-ink">{{ $latest?->snr ?? '-' }}</p>
                    <p class="text-xs text-muted mt-2">dB</p>
                </div>
            </div>
            <div class="p-4 bg-canvas/60 rounded">
                <div class="text-center">
                    <p class="text-muted text-sm mb-2">Packet Loss</p>
                    <p class="text-3xl font-bold text-ink">{{ $latest?->packet_loss ?? '-' }}%</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Konfigurasi Firmware -->
    <div class="card">
        <div class="flex items-start justify-between gap-4 mb-4">
            <div>
                <h3 class="text-lg font-semibold text-ink">Konfigurasi Firmware Node</h3>
                <p class="text-sm text-muted">Masukkan nilai berikut ke program perangkat agar datanya dikenali sistem.</p>
            </div>
            @if($device->gateway)
                <button type="button" onclick="copyFirmwareConfig()" class="shrink-0 btn btn-primary text-sm">Salin</button>
            @endif
        </div>

        @if($device->gateway)
            @php
                // Node hanya mengirim nomor paket dan data sensor
                $nodePayload = [
                    'node_id' => $device->node_id,
                    'packet_no' => 1,
                    'temperature' => 28.5,
                    'voltage' => 12.1,
                    'current' => 1.2,
                    'power' => 14.5,
                    'irradiance' => 650,
                ];
                // Gateway menambahkan RSSI, SNR, dan packet loss sebelum menerbitkan ke MQTT
                $mqttPayload = $nodePayload + [
                    'rssi' => -72,
                    'snr' => 8.5,
                    'packet_loss' => 0,
                ];
            @endphp
            <div id="firmware-config" class="space-y-2 font-mono text-sm">
                <div class="flex justify-between gap-4 p-3 bg-canvas/60 rounded"><span class="text-muted">NODE_ID</span><span class="text-ink">{{ $device->node_id }}</span></div>
                <div class="flex justify-between gap-4 p-3 bg-canvas/60 rounded"><span class="text-muted">GATEWAY_CODE</span><span class="text-ink">{{ $device->gateway->gateway_code }}</span></div>
                <div class="flex justify-between gap-4 p-3 bg-canvas/60 rounded"><span class="text-muted">MQTT_TOPIC</span><span class="text-ink break-all text-right">{{ $device->gateway->mqttDataTopic() }}</span></div>
            </div>

            <p class="text-sm text-ink mt-6 mb-2">Pesan yang dikirim Node via LoRa (nilai contoh):</p>
            <pre class="bg-ink/40 text-lime text-xs p-4 rounded overflow-x-auto">{{ json_encode($nodePayload, JSON_PRETTY_PRINT) }}</pre>

            <p class="text-sm text-ink mt-6 mb-2">Pesan yang diterbitkan Gateway ke MQTT (nilai contoh). RSSI, SNR, dan packet loss ditambahkan Gateway:</p>
            <pre class="bg-ink/40 text-lime text-xs p-4 rounded overflow-x-auto">{{ json_encode($mqttPayload, JSON_PRETTY_PRINT) }}</pre>
            <p class="text-xs text-muted mt-2">Kolom <span class="font-mono">node_id</span> harus sama dengan NODE_ID di atas. <span class="font-mono">packet_no</span> harus bertambah untuk setiap paket.</p>
        @else
            <p class="text-sm text-muted">Perangkat belum terhubung ke Gateway. Edit perangkat dan pilih Gateway untuk melihat konfigurasi.</p>
        @endif
    </div>

    <!-- Tren 24 jam (rata-rata per 30 menit) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card">
            <h3 class="text-lg font-semibold text-ink mb-4">Trend Suhu (24 Jam)</h3>
            <div class="relative h-64">
                <canvas id="chart-temperature" aria-label="Trend suhu 24 jam"></canvas>
            </div>
        </div>
        <div class="card">
            <h3 class="text-lg font-semibold text-ink mb-4">Trend Tegangan & Arus (24 Jam)</h3>
            <div class="relative h-64">
                <canvas id="chart-voltage-current" aria-label="Trend tegangan dan arus 24 jam"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Alerts -->
    <div class="card">
        <h3 class="text-lg font-semibold text-ink mb-4">Peringatan Terbaru</h3>
        <div class="space-y-3">
            @forelse($alerts as $alert)
                <div class="p-4 border-l-4 border-red-500 bg-red-50 rounded">
                    <p class="font-semibold text-red-800">{{ $alert->alert_type === 'offline' ? 'Device Offline' : 'Threshold Breach' }}</p>
                    <p class="text-sm text-red-600">Status: {{ $alert->status }}</p>
                    <p class="text-xs text-red-500 mt-2">{{ $alert->triggered_at->diffForHumans() }}</p>
                </div>
            @empty
                <p class="text-sm text-muted">Belum ada peringatan untuk perangkat ini.</p>
            @endforelse
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js"></script>
<script>
(function () {
    const TREND = @json($trend24);
    const hasData = (arr) => arr.some((v) => v !== null);

    const line = (label, data, color) => ({
        label,
        data,
        borderColor: color,
        backgroundColor: color + '22',
        borderWidth: 2,
        pointRadius: 3,
        pointHoverRadius: 5,
        tension: 0.3,
        spanGaps: true,
    });

    const baseOptions = (yTitle) => ({
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        scales: {
            x: { ticks: { maxTicksLimit: 12, autoSkip: true }, grid: { display: false } },
            y: { title: { display: true, text: yTitle } },
        },
        plugins: { legend: { position: 'bottom' } },
    });

    const showEmpty = (id) => {
        const canvas = document.getElementById(id);
        canvas.parentElement.innerHTML = '<div class="h-64 flex items-center justify-center text-muted text-sm">Belum ada data dalam 24 jam terakhir.</div>';
    };

    if (hasData(TREND.temperature)) {
        new Chart(document.getElementById('chart-temperature'), {
            type: 'line',
            data: { labels: TREND.labels, datasets: [line('Suhu (°C)', TREND.temperature, '#6f9f12')] },
            options: baseOptions('°C'),
        });
    } else {
        showEmpty('chart-temperature');
    }

    if (hasData(TREND.voltage) || hasData(TREND.current)) {
        const options = baseOptions('V');
        options.scales.y1 = { position: 'right', title: { display: true, text: 'A' }, grid: { drawOnChartArea: false } };
        new Chart(document.getElementById('chart-voltage-current'), {
            type: 'line',
            data: {
                labels: TREND.labels,
                datasets: [
                    { ...line('Tegangan (V)', TREND.voltage, '#2563eb'), yAxisID: 'y' },
                    { ...line('Arus (A)', TREND.current, '#ea580c'), yAxisID: 'y1' },
                ],
            },
            options,
        });
    } else {
        showEmpty('chart-voltage-current');
    }
})();
</script>
<script>
function copyFirmwareConfig() {
    const text = document.getElementById('firmware-config')?.innerText ?? '';
    navigator.clipboard.writeText(text)
        .then(() => showToast('Konfigurasi disalin.', 'success'))
        .catch(() => showToast('Gagal menyalin. Salin secara manual.', 'error'));
}
</script>
@endsection
