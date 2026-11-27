@extends('layouts.app')

@section('title', 'Konfigurasi Alert - IoT Dashboard')
@section('page-title', 'Konfigurasi Ambang Batas Alert')
@section('page-subtitle', 'Atur parameter peringatan untuk semua perangkat')

@section('content')
<form method="POST" action="{{ route('alerts.configuration.update') }}" class="space-y-6">
    @csrf

    <!-- Threshold Settings -->
    <div class="card">
        <h3 class="text-lg font-semibold text-ink mb-4">Ambang Batas Parameter Sensor</h3>
        <p class="text-xs text-muted mb-4">Berlaku untuk semua perangkat yang tidak memiliki ambang batas khusus.</p>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-canvas/60 border-b border-line">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-ink">Parameter</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-ink">Unit</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-ink">Min</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-ink">Max</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach($sensors as $sensor => $info)
                    @php $current = $thresholds->get($sensor); @endphp
                    <tr class="hover:bg-canvas/60">
                        <td class="px-6 py-4 text-sm font-semibold text-ink">{{ $info['label'] }}</td>
                        <td class="px-6 py-4 text-sm text-muted">{{ $info['unit'] }}</td>
                        <td class="px-6 py-4 text-center">
                            <input type="number" step="0.01" name="thresholds[{{ $sensor }}][min]" value="{{ old("thresholds.$sensor.min", $current?->min_value) }}" class="w-24 px-3 py-1 border border-line rounded text-sm focus:outline-none focus:ring-2 focus:ring-primary/15 focus:border-primary" required>
                            @error("thresholds.$sensor.min")<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        </td>
                        <td class="px-6 py-4 text-center">
                            <input type="number" step="0.01" name="thresholds[{{ $sensor }}][max]" value="{{ old("thresholds.$sensor.max", $current?->max_value) }}" class="w-24 px-3 py-1 border border-line rounded text-sm focus:outline-none focus:ring-2 focus:ring-primary/15 focus:border-primary" required>
                            @error("thresholds.$sensor.max")<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn btn-primary">Simpan Konfigurasi</button>
            <a href="{{ route('alerts.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </div>

    <!-- Alert Trigger Rules -->
    <div class="card hidden">
        <h3 class="text-lg font-semibold text-ink mb-4">Aturan Pemicu Peringatan</h3>

        <div class="space-y-4">
            <div class="p-4 border-l-4 border-red-500 bg-red-50 rounded">
                <h4 class="font-semibold text-ink">1. Threshold Breach</h4>
                <p class="text-sm text-muted mt-1">Peringatan dipicu ketika nilai sensor melampaui batas atas atau bawah yang telah dikonfigurasi.</p>
                <div class="mt-2 text-xs text-muted">
                    <p>Contoh: Jika suhu > 35°C atau < 15°C, sistem akan mengeluarkan alert</p>
                </div>
            </div>

            <div class="p-4 border-l-4 border-orange-500 bg-orange-50 rounded">
                <h4 class="font-semibold text-ink">2. Device Offline</h4>
                <p class="text-sm text-muted mt-1">Peringatan dipicu ketika tidak ada data masuk dari perangkat melebihi durasi timeout yang telah diatur. Alert otomatis ditutup saat perangkat kembali mengirim data.</p>
                <div class="mt-2 text-xs text-muted">
                    <p>Contoh: Jika timeout = 15 menit dan tidak ada data selama 15 menit, alert "Device Offline" akan dipicu</p>
                </div>
            </div>

            <div class="p-4 border-l-4 border-primary bg-primary-soft rounded">
                <h4 class="font-semibold text-ink">3. Network Quality</h4>
                <p class="text-sm text-muted mt-1">Peringatan dipicu ketika kualitas sinyal LoRa menurun signifikan (RSSI, SNR, atau Packet Loss).</p>
                <div class="mt-2 text-xs text-muted">
                    <p>Contoh: RSSI < -100 dBm atau Packet Loss > 10% akan menghasilkan alert</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Information Box -->
    <div class="bg-primary-soft border border-lime/40 rounded-lg p-6">
        <h4 class="font-semibold text-ink mb-2">💡 Catatan Penting</h4>
        <ul class="text-sm text-ink space-y-1">
            <li>• Pastikan offline timeout minimal 10-15 menit untuk menghindari false alarm (toleransi 2-3 siklus beacon)</li>
            <li>• Ambang batas yang terlalu ketat dapat menyebabkan banyak false positive</li>
            <li>• Setiap perubahan ambang batas dan timeout dicatat dalam Audit Log</li>
            <li>• Alert dapat dipicu untuk satu atau lebih perangkat sekaligus</li>
        </ul>
    </div>
</form>
@endsection
