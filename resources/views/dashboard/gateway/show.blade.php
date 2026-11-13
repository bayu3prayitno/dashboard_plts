@extends('layouts.app')

@section('title', 'Detail Gateway - IoT Dashboard')
@section('page-title', $gateway['name'])
@section('page-subtitle', 'Monitoring detail infrastruktur ' . $gateway['device_id'])

@section('content')
<div class="space-y-6">
    <!-- Gateway Information -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Device Info -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Informasi Gateway</h3>
            <div class="space-y-3">
                <div>
                    <p class="text-gray-500 text-sm">ID Gateway</p>
                    <p class="font-mono text-gray-800">{{ $gateway['device_id'] }}</p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Nama</p>
                    <p class="text-gray-800">{{ $gateway['name'] }}</p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Lokasi</p>
                    <p class="text-gray-800">{{ $gateway['location'] }}</p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Firmware Version</p>
                    <p class="text-gray-800">{{ $gateway['firmware_version'] }}</p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Beacon Interval</p>
                    <p class="text-gray-800">{{ $gateway['beacon_interval'] }}</p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Update Terakhir</p>
                    <p class="text-gray-800">{{ $gateway['last_communication'] }}</p>
                </div>
            </div>
        </div>

        <!-- Infrastructure Health -->
        <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Kesehatan Infrastruktur</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                    <div>
                        <p class="font-semibold text-gray-800">Gateway Connection</p>
                        <p class="text-xs text-gray-600">Koneksi utama Gateway</p>
                    </div>
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        <span class="inline-block w-2 h-2 bg-green-600 rounded-full mr-2 animate-pulse"></span>
                        {{ ucfirst($gateway['infrastructure_health']['gateway_connection']) }}
                    </span>
                </div>

                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                    <div>
                        <p class="font-semibold text-gray-800">MQTT Broker</p>
                        <p class="text-xs text-gray-600">Koneksi ke MQTT server</p>
                    </div>
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        <span class="inline-block w-2 h-2 bg-green-600 rounded-full mr-2 animate-pulse"></span>
                        {{ ucfirst($gateway['infrastructure_health']['mqtt_broker']) }}
                    </span>
                </div>

                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                    <div>
                        <p class="font-semibold text-gray-800">Internet Connection</p>
                        <p class="text-xs text-gray-600">Koneksi internet WAN</p>
                    </div>
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        <span class="inline-block w-2 h-2 bg-green-600 rounded-full mr-2 animate-pulse"></span>
                        {{ ucfirst($gateway['infrastructure_health']['internet_connection']) }}
                    </span>
                </div>

                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                    <div>
                        <p class="font-semibold text-gray-800">SD Card</p>
                        <p class="text-xs text-gray-600">Status backup data</p>
                    </div>
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        <span class="inline-block w-2 h-2 bg-green-600 rounded-full mr-2 animate-pulse"></span>
                        {{ ucfirst($gateway['infrastructure_health']['sd_card']) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Connected Nodes -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Node Sensor Terhubung</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($gateway['connected_nodes'] as $node)
            <div class="p-4 border border-gray-200 rounded-lg hover:border-blue-500 transition">
                <div class="flex items-center mb-2">
                    <span class="inline-block w-2 h-2 bg-green-600 rounded-full mr-2 animate-pulse"></span>
                    <p class="font-semibold text-gray-800">{{ $node }}</p>
                </div>
                <p class="text-xs text-gray-600">Online - Signal Kuat</p>
                <div class="mt-3 pt-3 border-t border-gray-200">
                    <p class="text-xs text-gray-500">RSSI: -85 dBm | SNR: 10 dB</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Performance Metrics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Network Performance -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Performa Jaringan</h3>
            <div class="space-y-3">
                <div>
                    <p class="text-gray-600 text-sm mb-2">Packet Transmission Success Rate</p>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-600 h-2 rounded-full" style="width: 98%"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">98% - Excellent</p>
                </div>
                <div>
                    <p class="text-gray-600 text-sm mb-2">Average Response Time</p>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: 65%"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">245 ms</p>
                </div>
                <div>
                    <p class="text-gray-600 text-sm mb-2">Uptime</p>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-600 h-2 rounded-full" style="width: 99.95%"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">99.95% - 15 days</p>
                </div>
            </div>
        </div>

        <!-- Data Backup Status -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Status Backup SD Card</h3>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <p class="text-gray-600 text-sm">SD Card Usage</p>
                        <p class="font-semibold text-gray-800">45%</p>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-yellow-500 h-2 rounded-full" style="width: 45%"></div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2 pt-2 border-t">
                    <div>
                        <p class="text-xs text-gray-600">Total Capacity</p>
                        <p class="text-sm font-semibold text-gray-800">32 GB</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600">Used</p>
                        <p class="text-sm font-semibold text-gray-800">14.4 GB</p>
                    </div>
                </div>
                <div class="p-3 bg-blue-50 rounded text-xs text-blue-800">
                    <p>💾 Last backup: 30 minutes ago</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Events Log -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Log Aktivitas Terbaru</h3>
        <div class="space-y-3 max-h-96 overflow-y-auto">
            <div class="p-3 border-l-4 border-green-500 bg-green-50 rounded">
                <p class="text-sm font-semibold text-green-800">Connected</p>
                <p class="text-xs text-green-600">Gateway terhubung ke MQTT broker</p>
                <p class="text-xs text-green-500">10 menit yang lalu</p>
            </div>
            <div class="p-3 border-l-4 border-blue-500 bg-blue-50 rounded">
                <p class="text-sm font-semibold text-blue-800">Data Received</p>
                <p class="text-xs text-blue-600">Menerima data dari 3 node sensor</p>
                <p class="text-xs text-blue-500">15 menit yang lalu</p>
            </div>
            <div class="p-3 border-l-4 border-yellow-500 bg-yellow-50 rounded">
                <p class="text-sm font-semibold text-yellow-800">Backup Completed</p>
                <p class="text-xs text-yellow-600">SD Card backup selesai (156 MB)</p>
                <p class="text-xs text-yellow-500">30 menit yang lalu</p>
            </div>
        </div>
    </div>
</div>
@endsection
