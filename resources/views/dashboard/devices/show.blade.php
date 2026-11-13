@extends('layouts.app')

@section('title', 'Detail Perangkat - IoT Dashboard')
@section('page-title', $device['name'])
@section('page-subtitle', 'Detail dan monitoring perangkat ' . $device['device_id'])

@section('content')
<div class="space-y-6">
    <!-- Device Information -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Device Info Card -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Informasi Perangkat</h3>
            <div class="space-y-3">
                <div>
                    <p class="text-gray-500 text-sm">ID Perangkat</p>
                    <p class="font-mono text-gray-800">{{ $device['device_id'] }}</p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Nama</p>
                    <p class="text-gray-800">{{ $device['name'] }}</p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Lokasi</p>
                    <p class="text-gray-800">{{ $device['location'] }}</p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Gateway Terhubung</p>
                    <p class="text-gray-800">{{ $device['gateway'] }}</p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Status</p>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 mt-1">
                        <span class="inline-block w-2 h-2 bg-green-600 rounded-full mr-2 animate-pulse"></span>
                        {{ ucfirst($device['status']) }}
                    </span>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Update Terakhir</p>
                    <p class="text-gray-800">{{ $device['last_update'] }}</p>
                </div>
            </div>
            <div class="mt-6 pt-6 border-t border-gray-200 flex gap-2">
                <a href="{{ route('devices.edit', $device['id']) }}" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-center">Edit</a>
                <button onclick="deleteDevice()" class="flex-1 bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">Hapus</button>
            </div>
        </div>

        <!-- Current Sensor Values -->
        <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Nilai Sensor Terkini</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="p-4 bg-red-50 rounded border border-red-200">
                    <p class="text-gray-600 text-sm">Suhu</p>
                    <p class="text-3xl font-bold text-red-600">{{ $device['sensor_data']['temperature'] }}°C</p>
                    <p class="text-xs text-gray-500 mt-1">Normal</p>
                </div>
                <div class="p-4 bg-blue-50 rounded border border-blue-200">
                    <p class="text-gray-600 text-sm">Tegangan</p>
                    <p class="text-3xl font-bold text-blue-600">{{ $device['sensor_data']['voltage'] }}V</p>
                    <p class="text-xs text-gray-500 mt-1">Normal</p>
                </div>
                <div class="p-4 bg-yellow-50 rounded border border-yellow-200">
                    <p class="text-gray-600 text-sm">Arus</p>
                    <p class="text-3xl font-bold text-yellow-600">{{ $device['sensor_data']['current'] }}A</p>
                    <p class="text-xs text-gray-500 mt-1">Normal</p>
                </div>
                <div class="p-4 bg-purple-50 rounded border border-purple-200">
                    <p class="text-gray-600 text-sm">Daya</p>
                    <p class="text-3xl font-bold text-purple-600">{{ $device['sensor_data']['power'] }}W</p>
                    <p class="text-xs text-gray-500 mt-1">Normal</p>
                </div>
                <div class="p-4 bg-indigo-50 rounded border border-indigo-200 col-span-2">
                    <p class="text-gray-600 text-sm">Irradiance</p>
                    <p class="text-3xl font-bold text-indigo-600">{{ $device['sensor_data']['irradiance'] }}W/m²</p>
                    <p class="text-xs text-gray-500 mt-1">Optimal</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Network Metrics -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Metrik Kesehatan Jaringan LoRa</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="p-4 bg-gray-50 rounded">
                <div class="text-center">
                    <p class="text-gray-600 text-sm mb-2">RSSI (Received Signal Strength)</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $device['network_metrics']['rssi'] }}</p>
                    <p class="text-xs text-gray-500 mt-2">dBm - Signal Baik</p>
                </div>
            </div>
            <div class="p-4 bg-gray-50 rounded">
                <div class="text-center">
                    <p class="text-gray-600 text-sm mb-2">SNR (Signal-to-Noise Ratio)</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $device['network_metrics']['snr'] }}</p>
                    <p class="text-xs text-gray-500 mt-2">dB - Signal Jernih</p>
                </div>
            </div>
            <div class="p-4 bg-gray-50 rounded">
                <div class="text-center">
                    <p class="text-gray-600 text-sm mb-2">Packet Loss</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $device['network_metrics']['packet_loss'] }}%</p>
                    <p class="text-xs text-gray-500 mt-2">Minimal Loss</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Historical Data Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Temperature Chart -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Trend Suhu (24 Jam)</h3>
            <div class="h-64 bg-gray-50 rounded flex items-center justify-center">
                <p class="text-gray-500">Grafik akan ditampilkan di sini</p>
            </div>
        </div>

        <!-- Voltage & Current Chart -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Trend Tegangan & Arus (24 Jam)</h3>
            <div class="h-64 bg-gray-50 rounded flex items-center justify-center">
                <p class="text-gray-500">Grafik akan ditampilkan di sini</p>
            </div>
        </div>
    </div>

    <!-- Recent Alerts -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Peringatan Terbaru</h3>
        <div class="space-y-3">
            <div class="p-4 border-l-4 border-red-500 bg-red-50 rounded">
                <p class="font-semibold text-red-800">Threshold Breach - Suhu Maksimal</p>
                <p class="text-sm text-red-600">Suhu mencapai 32.5°C (batas: 30°C)</p>
                <p class="text-xs text-red-500 mt-2">2 jam yang lalu</p>
            </div>
            <div class="p-4 border-l-4 border-yellow-500 bg-yellow-50 rounded">
                <p class="font-semibold text-yellow-800">Anomali Detected</p>
                <p class="text-sm text-yellow-600">Perubahan mendadak pada pembacaan arus</p>
                <p class="text-xs text-yellow-500 mt-2">5 jam yang lalu</p>
            </div>
        </div>
    </div>
</div>

<script>
function deleteDevice() {
    if (confirm('Apakah Anda yakin ingin menghapus perangkat ini?')) {
        // Implement delete logic
        console.log('Delete device');
    }
}
</script>
@endsection
