@extends('layouts.app')

@section('title', 'Edit Perangkat - IoT Dashboard')
@section('page-title', 'Edit Perangkat')
@section('page-subtitle', 'Perbarui informasi perangkat ' . $device->name)

@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-lg shadow p-8">
        <form method="POST" action="{{ route('devices.update', $device) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Node ID (Read-only) -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">ID Node</label>
                <input type="text" value="DEVICE_{{ str_pad($device->node_id, 3, '0', STR_PAD_LEFT) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100" disabled>
                <p class="text-xs text-gray-500 mt-1">Identitas perangkat tidak dapat diubah</p>
            </div>

            <!-- Device Name -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Perangkat</label>
                <input type="text" name="name" value="{{ old('name', $device->name) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <!-- Location -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Lokasi</label>
                <input type="text" name="location" value="{{ old('location', $device->location) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                @error('location')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <!-- Gateway -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Gateway Terhubung</label>
                <select name="gateway_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="">Pilih Gateway</option>
                    @foreach($gateways as $gateway)
                        <option value="{{ $gateway->id }}" @selected(old('gateway_id', $device->gateway_id) == $gateway->id)>{{ $gateway->name }}</option>
                    @endforeach
                </select>
                @error('gateway_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <!-- Node ID field for validation (tetap dikirim agar update konsisten dengan store) -->
            <input type="hidden" name="node_id" value="{{ $device->node_id }}">

            <!-- Status -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="active" @selected($device->is_active)>Aktif</option>
                    <option value="inactive" @selected(! $device->is_active)>Nonaktif</option>
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
