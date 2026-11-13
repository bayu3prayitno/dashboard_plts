@extends('layouts.app')

@section('title', 'Pengaturan Sistem - IoT Dashboard')
@section('page-title', 'Pengaturan Sistem Global')
@section('page-subtitle', 'Konfigurasi sistem, timezone, dan MQTT settings')

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

            <!-- MQTT Settings Section -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Konfigurasi MQTT Broker</h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">MQTT Broker URL</label>
                        <input type="text" name="mqtt_broker_url" value="{{ $settings['mqtt_broker_url'] }}" placeholder="mqtt://broker.example.com" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">MQTT Username</label>
                        <input type="text" name="mqtt_username" value="{{ $settings['mqtt_username'] }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">MQTT Password</label>
                        <input type="password" name="mqtt_password" placeholder="●●●●●●●●" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">MQTT Port</label>
                        <input type="number" name="mqtt_port" value="1883" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex gap-4 pt-6 border-t">
                <button type="submit" class="flex-1 bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 font-semibold">Simpan Pengaturan</button>
                <button type="button" onclick="testMQTT()" class="flex-1 bg-gray-300 text-gray-800 px-6 py-2 rounded-lg hover:bg-gray-400 font-semibold">Test Koneksi</button>
            </div>
        </form>
    </div>
</div>

<script>
function testMQTT() {
    alert('Testing MQTT connection...');
    // Implement MQTT test logic
}
</script>
@endsection
