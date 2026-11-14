@extends('layouts.app')

@use('App\Support\Format')

@section('title', 'Manajemen Gateway - IoT Dashboard')
@section('page-title', 'Manajemen Gateway')
@section('page-subtitle', 'Monitor kesehatan dan status infrastruktur Gateway')

@section('content')
<div class="space-y-6">
    <!-- Action Bar -->
    <div class="flex justify-end">
        <a href="{{ route('gateway.create') }}" class="btn btn-primary">Tambah Gateway</a>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600 text-sm">Total Gateway</p>
            <p class="text-3xl font-bold text-gray-800">{{ $gateways->count() }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600 text-sm">Gateway Online</p>
            <p class="text-3xl font-bold text-green-600">{{ $gateways->where('status', 'online')->count() }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600 text-sm">Gateway Offline</p>
            <p class="text-3xl font-bold text-red-600">{{ $gateways->where('status', 'offline')->count() }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600 text-sm">Total Node</p>
            <p class="text-3xl font-bold text-blue-600">{{ $gateways->sum('devices_count') }}</p>
        </div>
    </div>

    <!-- Gateways Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Nama Gateway</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Kode</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Lokasi</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Status Koneksi</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">MQTT</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Internet</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Jumlah Node</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Komunikasi Terakhir</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($gateways as $gateway)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm font-semibold text-gray-800">{{ $gateway->name }}</td>
                    <td class="px-6 py-4 text-sm font-mono text-gray-600">{{ $gateway->gateway_code }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $gateway->location }}</td>
                    <td class="px-6 py-4 text-center"><x-status-badge :status="$gateway->status" /></td>
                    <td class="px-6 py-4 text-center"><x-status-badge :status="$gateway->mqtt_status" /></td>
                    <td class="px-6 py-4 text-center"><x-status-badge :status="$gateway->internet_status" /></td>
                    <td class="px-6 py-4 text-center text-sm font-semibold text-gray-800">{{ $gateway->devices_count }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ Format::relTime($gateway->last_seen_at) }}</td>
                    <td class="px-6 py-4 text-sm">
                        <a href="{{ route('gateway.show', $gateway) }}" class="text-blue-600 hover:text-blue-800 font-semibold">Detail</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-6 py-4 text-center text-sm text-gray-500">Belum ada gateway terdaftar.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
