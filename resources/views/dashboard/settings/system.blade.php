@extends('layouts.app')

@section('title', 'Pengaturan Sistem - IoT Dashboard')
@section('page-title', 'Pengaturan Sistem Global')
@section('page-subtitle', 'Konfigurasi zona waktu, retensi data, dan offline timeout')

@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-lg shadow p-8 space-y-6">
        <form method="POST" action="{{ route('settings.systemUpdate') }}">
            @csrf

            <!-- Timezone -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Timezone</label>
                <select name="timezone" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="Asia/Jakarta" @selected($settings['timezone'] === 'Asia/Jakarta')>Asia/Jakarta (WIB)</option>
                    <option value="Asia/Makassar" @selected($settings['timezone'] === 'Asia/Makassar')>Asia/Makassar (WITA)</option>
                    <option value="Asia/Jayapura" @selected($settings['timezone'] === 'Asia/Jayapura')>Asia/Jayapura (WIT)</option>
                    <option value="UTC" @selected($settings['timezone'] === 'UTC')>UTC</option>
                </select>
            </div>

            <!-- Data Retention -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Retensi Data (Hari)</label>
                <input type="number" name="data_retention_days" value="{{ $settings['data_retention_days'] }}" min="30" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-500 mt-1">Data lebih lama dari ini akan diarsipkan atau dihapus</p>
            </div>

            <!-- Offline Timeout -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Offline Timeout (Menit)</label>
                <input type="number" name="offline_timeout_minutes" value="{{ $settings['offline_timeout_minutes'] }}" min="5" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-500 mt-1">Durasi sebelum perangkat dianggap offline</p>
            </div>

            <!-- Beacon Interval -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Beacon Interval (Menit)</label>
                <input type="number" name="beacon_interval_minutes" value="{{ $settings['beacon_interval_minutes'] }}" disabled class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100">
                <p class="text-xs text-gray-500 mt-1">Interval pemancar Gateway (read-only, diatur di firmware)</p>
            </div>

            <!-- Buttons -->
            <div class="flex gap-4 pt-6 border-t">
                <button type="submit" class="flex-1 bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 font-semibold">Simpan Pengaturan</button>
            </div>
        </form>
    </div>
</div>
@endsection
