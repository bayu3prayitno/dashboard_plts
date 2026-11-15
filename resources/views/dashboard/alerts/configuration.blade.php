@extends('layouts.app')

@section('title', 'Konfigurasi Alert - IoT Dashboard')
@section('page-title', 'Konfigurasi Ambang Batas Alert')
@section('page-subtitle', 'Atur parameter peringatan untuk semua perangkat')

@section('content')
<form method="POST" action="{{ route('alerts.configuration.update') }}" class="space-y-6">
    @csrf

    <!-- Global Settings -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Pengaturan Global</h3>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Offline Timeout (Menit)</label>
                <input type="number" name="offline_timeout" value="{{ old('offline_timeout', $offlineTimeout) }}" min="5" max="1440" class="w-full md:w-64 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                <p class="text-xs text-gray-500 mt-1">Durasi sebelum perangkat dianggap offline (minimal 10-15 menit untuk toleransi 2-3 siklus pengiriman)</p>
                @error('offline_timeout')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    <!-- Threshold Settings -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Ambang Batas Parameter Sensor</h3>
        <p class="text-xs text-gray-500 mb-4">Berlaku untuk semua perangkat yang tidak memiliki ambang batas khusus.</p>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Parameter</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Unit</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Min</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Max</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($sensors as $sensor => $info)
                    @php $current = $thresholds->get($sensor); @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-semibold text-gray-800">{{ $info['label'] }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $info['unit'] }}</td>
                        <td class="px-6 py-4 text-center">
                            <input type="number" step="0.01" name="thresholds[{{ $sensor }}][min]" value="{{ old("thresholds.$sensor.min", $current?->min_value) }}" class="w-24 px-3 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            @error("thresholds.$sensor.min")<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        </td>
                        <td class="px-6 py-4 text-center">
                            <input type="number" step="0.01" name="thresholds[{{ $sensor }}][max]" value="{{ old("thresholds.$sensor.max", $current?->max_value) }}" class="w-24 px-3 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            @error("thresholds.$sensor.max")<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex gap-3">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Simpan Konfigurasi</button>
            <a href="{{ route('alerts.index') }}" class="bg-gray-300 text-gray-800 px-6 py-2 rounded-lg hover:bg-gray-400">Batal</a>
        </div>
    </div>

    <!-- Alert Trigger Rules -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Aturan Pemicu Peringatan</h3>

        <div class="space-y-4">
            <div class="p-4 border-l-4 border-red-500 bg-red-50 rounded">
                <h4 class="font-semibold text-gray-800">1. Threshold Breach</h4>
                <p class="text-sm text-gray-600 mt-1">Peringatan dipicu ketika nilai sensor melampaui batas atas atau bawah yang telah dikonfigurasi.</p>
                <div class="mt-2 text-xs text-gray-500">
                    <p>Contoh: Jika suhu > 35°C atau < 15°C, sistem akan mengeluarkan alert</p>
                </div>
            </div>

            <div class="p-4 border-l-4 border-orange-500 bg-orange-50 rounded">
                <h4 class="font-semibold text-gray-800">2. Device Offline</h4>
                <p class="text-sm text-gray-600 mt-1">Peringatan dipicu ketika tidak ada data masuk dari perangkat melebihi durasi timeout yang telah diatur. Alert otomatis ditutup saat perangkat kembali mengirim data.</p>
                <div class="mt-2 text-xs text-gray-500">
                    <p>Contoh: Jika timeout = 15 menit dan tidak ada data selama 15 menit, alert "Device Offline" akan dipicu</p>
                </div>
            </div>

            <div class="p-4 border-l-4 border-blue-500 bg-blue-50 rounded">
                <h4 class="font-semibold text-gray-800">3. Network Quality</h4>
                <p class="text-sm text-gray-600 mt-1">Peringatan dipicu ketika kualitas sinyal LoRa menurun signifikan (RSSI, SNR, atau Packet Loss).</p>
                <div class="mt-2 text-xs text-gray-500">
                    <p>Contoh: RSSI < -100 dBm atau Packet Loss > 10% akan menghasilkan alert</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Information Box -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <h4 class="font-semibold text-blue-900 mb-2">💡 Catatan Penting</h4>
        <ul class="text-sm text-blue-800 space-y-1">
            <li>• Pastikan offline timeout minimal 10-15 menit untuk menghindari false alarm (toleransi 2-3 siklus beacon)</li>
            <li>• Ambang batas yang terlalu ketat dapat menyebabkan banyak false positive</li>
            <li>• Setiap perubahan ambang batas dan timeout dicatat dalam Audit Log</li>
            <li>• Alert dapat dipicu untuk satu atau lebih perangkat sekaligus</li>
        </ul>
    </div>
</form>
@endsection
