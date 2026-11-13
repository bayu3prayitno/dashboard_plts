@extends('layouts.app')

@section('title', 'Konfigurasi Alert - IoT Dashboard')
@section('page-title', 'Konfigurasi Ambang Batas Alert')
@section('page-subtitle', 'Atur parameter peringatan untuk semua perangkat')

@section('content')
<div class="space-y-6">
    <!-- Global Settings -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Pengaturan Global</h3>
        <form method="POST" action="{{ route('alerts.configuration') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Offline Timeout (Menit)</label>
                <input type="number" name="offline_timeout" value="{{ $offline_timeout }}" min="5" class="w-full md:w-64 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-500 mt-1">Durasi sebelum perangkat dianggap offline (minimal 10-15 menit untuk toleransi 2-3 siklus pengiriman)</p>
            </div>

            <div class="flex gap-3 pt-4">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Simpan Pengaturan</button>
            </div>
        </form>
    </div>

    <!-- Threshold Settings -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Ambang Batas Parameter Sensor</h3>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Parameter</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Unit</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Min</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Max</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($thresholds as $index => $threshold)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-semibold text-gray-800">{{ $threshold['parameter'] }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $threshold['unit'] }}</td>
                        <td class="px-6 py-4 text-center">
                            <input type="number" step="0.1" value="{{ $threshold['min'] }}" class="w-24 px-3 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </td>
                        <td class="px-6 py-4 text-center">
                            <input type="number" step="0.1" value="{{ $threshold['max'] }}" class="w-24 px-3 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </td>
                        <td class="px-6 py-4 text-center">
                            <button type="button" onclick="editThreshold({{ $index }})" class="text-blue-600 hover:text-blue-800 font-semibold text-sm">Edit</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex gap-3">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Simpan Ambang Batas</button>
            <button type="reset" class="bg-gray-300 text-gray-800 px-6 py-2 rounded-lg hover:bg-gray-400">Reset</button>
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
                <p class="text-sm text-gray-600 mt-1">Peringatan dipicu ketika tidak ada data masuk dari perangkat melebihi durasi timeout yang telah diatur.</p>
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
            <li>• Setiap perubahan threshold akan dicatat dalam Audit Log</li>
            <li>• Alert dapat dipicu untuk satu atau lebih perangkat sekaligus</li>
        </ul>
    </div>
</div>

<script>
function editThreshold(index) {
    console.log('Edit threshold:', index);
    // Implement edit modal or logic
}
</script>
@endsection
