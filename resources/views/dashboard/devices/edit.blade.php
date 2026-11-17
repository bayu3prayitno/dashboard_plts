@extends('layouts.app')

@section('title', 'Edit Perangkat - IoT Dashboard')
@section('page-title', 'Edit Perangkat')
@section('page-subtitle', 'Perbarui informasi perangkat ' . $device->name)

@section('content')
<div class="max-w-2xl">
    <div class="card p-8">
        <form method="POST" action="{{ route('devices.update', $device) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Node ID (Read-only) -->
            <div>
                <label class="block text-sm font-semibold text-ink mb-2">ID Node</label>
                <input type="text" value="DEVICE_{{ str_pad($device->node_id, 3, '0', STR_PAD_LEFT) }}" class="w-full px-4 py-2 border border-line rounded-lg bg-canvas" disabled>
                <p class="text-xs text-muted mt-1">Identitas perangkat tidak dapat diubah</p>
            </div>

            <!-- Device Name -->
            <div>
                <label class="block text-sm font-semibold text-ink mb-2">Nama Perangkat</label>
                <input type="text" name="name" value="{{ old('name', $device->name) }}" class="w-full px-4 py-2 rounded-full border border-line bg-white outline-none focus:ring-2 focus:ring-primary/15 focus:border-primary" required>
                @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <!-- Location -->
            <div>
                <label class="block text-sm font-semibold text-ink mb-2">Lokasi</label>
                <input type="text" name="location" value="{{ old('location', $device->location) }}" class="w-full px-4 py-2 rounded-full border border-line bg-white outline-none focus:ring-2 focus:ring-primary/15 focus:border-primary" required>
                @error('location')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <!-- Gateway -->
            <div>
                <label class="block text-sm font-semibold text-ink mb-2">Gateway Terhubung</label>
                <select name="gateway_id" class="w-full px-4 py-2 rounded-full border border-line bg-white outline-none focus:ring-2 focus:ring-primary/15 focus:border-primary" required>
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
                <label class="block text-sm font-semibold text-ink mb-2">Status</label>
                <select name="status" class="w-full px-4 py-2 rounded-full border border-line bg-white outline-none focus:ring-2 focus:ring-primary/15 focus:border-primary">
                    <option value="active" @selected($device->is_active)>Aktif</option>
                    <option value="inactive" @selected(! $device->is_active)>Nonaktif</option>
                </select>
            </div>

            <!-- Buttons -->
            <div class="flex gap-4 pt-6 border-t">
                <button type="submit" class="flex-1 btn btn-primary font-semibold">Perbarui Perangkat</button>
                <a href="{{ route('devices.index') }}" class="flex-1 btn btn-outline font-semibold text-center">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
