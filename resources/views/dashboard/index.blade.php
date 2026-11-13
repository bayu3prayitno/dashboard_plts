@extends('layouts.app')

@section('title', 'Dashboard - IoT Monitoring')
@section('page-title', 'Dashboard Utama')
@section('page-subtitle', 'Ringkasan status sistem dan monitoring real-time')

@section('content')
<div class="space-y-6">
    <!-- Status Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Devices -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="bg-blue-100 rounded-lg p-3">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2m0-10a2 2 0 012-2h2a2 2 0 012 2m0 0V7a2 2 0 012-2h2a2 2 0 012 2v10"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-500 text-sm">Total Perangkat</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $total_devices }}</p>
                </div>
            </div>
        </div>

        <!-- Online Devices -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="bg-green-100 rounded-lg p-3">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-500 text-sm">Perangkat Online</p>
                    <p class="text-2xl font-bold text-green-600">{{ $online_devices }}</p>
                </div>
            </div>
        </div>

        <!-- Offline Devices -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="bg-red-100 rounded-lg p-3">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-500 text-sm">Perangkat Offline</p>
                    <p class="text-2xl font-bold text-red-600">{{ $offline_devices }}</p>
                </div>
            </div>
        </div>

        <!-- Active Alerts -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="bg-yellow-100 rounded-lg p-3">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-500 text-sm">Peringatan Aktif</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ $active_alerts }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Infrastructure Status -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Gateway & Infrastructure Status -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Status Infrastruktur</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                    <span class="text-gray-700">Gateway Status</span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        <span class="inline-block w-2 h-2 bg-green-600 rounded-full mr-2"></span>
                        Terhubung
                    </span>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                    <span class="text-gray-700">MQTT Broker</span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        <span class="inline-block w-2 h-2 bg-green-600 rounded-full mr-2"></span>
                        Terhubung
                    </span>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                    <span class="text-gray-700">Internet</span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        <span class="inline-block w-2 h-2 bg-green-600 rounded-full mr-2"></span>
                        Terhubung
                    </span>
                </div>
                <div class="mt-4 p-3 bg-blue-50 rounded text-sm text-gray-600">
                    <p>Update terakhir: <span class="font-semibold">{{ $last_update }}</span></p>
                </div>
            </div>
        </div>

        <!-- Latest Sensor Data -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Data Sensor Terkini</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="p-3 bg-red-50 rounded">
                    <p class="text-gray-600 text-sm">Suhu</p>
                    <p class="text-2xl font-bold text-red-600">{{ $latest_sensor_data['temperature'] }}°C</p>
                </div>
                <div class="p-3 bg-blue-50 rounded">
                    <p class="text-gray-600 text-sm">Tegangan</p>
                    <p class="text-2xl font-bold text-blue-600">{{ $latest_sensor_data['voltage'] }}V</p>
                </div>
                <div class="p-3 bg-yellow-50 rounded">
                    <p class="text-gray-600 text-sm">Arus</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ $latest_sensor_data['current'] }}A</p>
                </div>
                <div class="p-3 bg-purple-50 rounded">
                    <p class="text-gray-600 text-sm">Daya</p>
                    <p class="text-2xl font-bold text-purple-600">{{ $latest_sensor_data['power'] }}W</p>
                </div>
                <div class="p-3 bg-indigo-50 rounded col-span-2">
                    <p class="text-gray-600 text-sm">Irradiance</p>
                    <p class="text-2xl font-bold text-indigo-600">{{ $latest_sensor_data['irradiance'] }}W/m²</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Trends -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Trend Chart -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Trend Suhu (24 Jam Terakhir)</h3>
            <div class="h-64 bg-gray-50 rounded flex items-center justify-center">
                <div class="text-center text-gray-500">
                    <p>Grafik akan ditampilkan di sini</p>
                    <p class="text-sm">Integrasi dengan Chart.js atau Recharts</p>
                </div>
            </div>
        </div>

        <!-- Active Alerts Summary -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Peringatan Aktif</h3>
                <a href="{{ route('alerts.index') }}" class="text-blue-600 text-sm hover:text-blue-800">Lihat Semua</a>
            </div>
            <div class="space-y-3 max-h-64 overflow-y-auto">
                <div class="p-3 border-l-4 border-red-500 bg-red-50 rounded">
                    <p class="font-semibold text-red-800">Threshold Breach</p>
                    <p class="text-sm text-red-600">Node Sensor 01 - Suhu melebihi batas maksimal</p>
                    <p class="text-xs text-red-500 mt-1">2 jam lalu</p>
                </div>
                <div class="p-3 border-l-4 border-orange-500 bg-orange-50 rounded">
                    <p class="font-semibold text-orange-800">Device Offline</p>
                    <p class="text-sm text-orange-600">Node Sensor 03 - Offline selama 35 menit</p>
                    <p class="text-xs text-orange-500 mt-1">35 menit lalu</p>
                </div>
                <div class="p-3 border-l-4 border-red-500 bg-red-50 rounded">
                    <p class="font-semibold text-red-800">Threshold Breach</p>
                    <p class="text-sm text-red-600">Node Sensor 02 - Arus melebihi batas maksimal</p>
                    <p class="text-xs text-red-500 mt-1">4 jam lalu</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Access Buttons -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Akses Cepat</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="{{ route('devices.create') }}" class="p-4 text-center bg-blue-50 hover:bg-blue-100 rounded-lg transition">
                <svg class="w-8 h-8 mx-auto text-blue-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <p class="text-sm font-semibold text-gray-800">Tambah Device</p>
            </a>
            <a href="{{ route('alerts.configuration') }}" class="p-4 text-center bg-yellow-50 hover:bg-yellow-100 rounded-lg transition">
                <svg class="w-8 h-8 mx-auto text-yellow-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                </svg>
                <p class="text-sm font-semibold text-gray-800">Konfigurasi Alert</p>
            </a>
            <a href="{{ route('historical.index') }}" class="p-4 text-center bg-green-50 hover:bg-green-100 rounded-lg transition">
                <svg class="w-8 h-8 mx-auto text-green-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <p class="text-sm font-semibold text-gray-800">Data Historis</p>
            </a>
            <a href="{{ route('gateway.index') }}" class="p-4 text-center bg-purple-50 hover:bg-purple-100 rounded-lg transition">
                <svg class="w-8 h-8 mx-auto text-purple-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
                <p class="text-sm font-semibold text-gray-800">Gateway</p>
            </a>
        </div>
    </div>
</div>
@endsection
