@extends('layouts.app')

@section('title', 'Edit Perangkat - IoT Dashboard')
@section('page-title', 'Edit Perangkat')
@section('page-subtitle', 'Perbarui informasi perangkat ' . $device['name'])

@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-lg shadow p-8">
        <form method="POST" action="{{ route('devices.update', $device['id']) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Device ID (Read-only) -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Device ID</label>
                <input type="text" value="DEVICE_00{{ $device['id'] }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100" disabled>
                <p class="text-xs text-gray-500 mt-1">Identitas perangkat tidak dapat diubah</p>
            </div>

            <!-- Device Name -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Perangkat</label>
                <input type="text" name="device_name" value="{{ $device['name'] }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>

            <!-- Location -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Lokasi</label>
                <select name="location" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="">Pilih Lokasi</option>
                    <option value="Lokasi A" @selected($device['location'] === 'Lokasi A')>Lokasi A</option>
                    <option value="Lokasi B" @selected($device['location'] === 'Lokasi B')>Lokasi B</option>
                    <option value="Lokasi C" @selected($device['location'] === 'Lokasi C')>Lokasi C</option>
                </select>
            </div>

            <!-- Gateway -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Gateway Terhubung</label>
                <select name="gateway" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="">Pilih Gateway</option>
                    <option value="Gateway 01" @selected($device['gateway'] === 'Gateway 01')>Gateway 01</option>
                    <option value="Gateway 02" @selected($device['gateway'] === 'Gateway 02')>Gateway 02</option>
                    <option value="Gateway 03" @selected($device['gateway'] === 'Gateway 03')>Gateway 03</option>
                </select>
            </div>

            <!-- Status -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="active">Aktif</option>
                    <option value="inactive">Nonaktif</option>
                </select>
            </div>

            <!-- Buttons -->
            <div class="flex gap-4 pt-6 border-t">
                <button type="submit" class="flex-1 bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 font-semibold">Perbarui Perangkat</button>
                <a href="{{ route('devices.index') }}" class="flex-1 bg-gray-300 text-gray-800 px-6 py-2 rounded-lg hover:bg-gray-400 font-semibold text-center">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
