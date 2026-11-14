@extends('layouts.app')

@section('title', 'Tambah Perangkat - IoT Dashboard')
@section('page-title', 'Tambah Perangkat Baru')
@section('page-subtitle', 'Registrasi node sensor baru ke sistem monitoring')

@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-lg shadow p-8">
        <form method="POST" action="{{ route('devices.store') }}" class="space-y-6">
            @csrf

            <!-- Node ID -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">ID Node</label>
                <input type="number" name="node_id" min="1" value="{{ old('node_id') }}" placeholder="1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                <p class="text-xs text-gray-500 mt-1">ID Node sesuai firmware (sesuai dengan firmware Node)</p>
                @error('node_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <!-- Device Name -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Perangkat</label>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="Node Sensor 01" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <!-- Location -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Lokasi</label>
                <input type="text" name="location" value="{{ old('location') }}" placeholder="Lokasi A" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                @error('location')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <!-- Gateway -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Gateway Terhubung</label>
                <select name="gateway_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="">Pilih Gateway</option>
                    @foreach($gateways as $gateway)
                        <option value="{{ $gateway->id }}" @selected(old('gateway_id') == $gateway->id)>{{ $gateway->name }}</option>
                    @endforeach
                </select>
                @if($gateways->isEmpty())
                    <p class="text-xs text-red-600 mt-1">Belum ada Gateway. Tambahkan Gateway terlebih dahulu.</p>
                @endif
                @error('gateway_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <!-- Buttons -->
            <div class="flex gap-4 pt-6 border-t">
                <button type="submit" class="flex-1 bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 font-semibold">Simpan Perangkat</button>
                <a href="{{ route('devices.index') }}" class="flex-1 bg-gray-300 text-gray-800 px-6 py-2 rounded-lg hover:bg-gray-400 font-semibold text-center">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
