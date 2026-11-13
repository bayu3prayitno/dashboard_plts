@extends('layouts.app')

@section('title', 'Tambah Perangkat - IoT Dashboard')
@section('page-title', 'Tambah Perangkat Baru')
@section('page-subtitle', 'Registrasi node sensor baru ke sistem monitoring')

@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-lg shadow p-8">
        <form method="POST" action="{{ route('devices.store') }}" class="space-y-6">
            @csrf

            <!-- Device ID -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Device ID</label>
                <input type="text" name="device_id" placeholder="DEVICE_001" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                <p class="text-xs text-gray-500 mt-1">Identitas unik perangkat (sesuai dengan firmware Node)</p>
            </div>

            <!-- Device Name -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Perangkat</label>
                <input type="text" name="device_name" placeholder="Node Sensor 01" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>

            <!-- Location -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Lokasi</label>
                <select name="location" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="">Pilih Lokasi</option>
                    <option value="Lokasi A">Lokasi A</option>
                    <option value="Lokasi B">Lokasi B</option>
                    <option value="Lokasi C">Lokasi C</option>
                    <option value="other">Lainnya</option>
                </select>
            </div>

            <!-- Gateway -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Gateway Terhubung</label>
                <select name="gateway" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="">Pilih Gateway</option>
                    <option value="Gateway 01">Gateway 01</option>
                    <option value="Gateway 02">Gateway 02</option>
                    <option value="Gateway 03">Gateway 03</option>
                </select>
            </div>

            <!-- Description -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Deskripsi</label>
                <textarea name="description" rows="4" placeholder="Deskripsi perangkat (opsional)" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>

            <!-- Sensor Configuration -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Konfigurasi Sensor</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Sensor Suhu</label>
                        <select name="temp_sensor" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="DS18B20">DS18B20</option>
                            <option value="DHT22">DHT22</option>
                            <option value="LM35">LM35</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Sensor Arus (INA219)</label>
                        <input type="checkbox" name="ina219" class="mt-2">
                        <span class="text-sm text-gray-600">Aktifkan sensor INA219</span>
                    </div>
                </div>
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
