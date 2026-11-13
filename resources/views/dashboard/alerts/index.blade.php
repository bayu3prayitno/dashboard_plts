@extends('layouts.app')

@section('title', 'Manajemen Peringatan - IoT Dashboard')
@section('page-title', 'Manajemen Peringatan')
@section('page-subtitle', 'Monitor dan kelola semua peringatan sistem')

@section('content')
<div class="space-y-6">
    <!-- Filter Bar -->
    <div class="flex justify-between items-center">
        <div class="flex gap-3">
            <select class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option>Semua Status</option>
                <option>Aktif</option>
                <option>Ditangani</option>
                <option>Selesai</option>
            </select>
            <select class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option>Semua Tipe</option>
                <option>Threshold Breach</option>
                <option>Device Offline</option>
            </select>
        </div>
        <a href="{{ route('alerts.configuration') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
            </svg>
            Konfigurasi Alert
        </a>
    </div>

    <!-- Alerts Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Tipe Alert</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Perangkat</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Pesan</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Nilai</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status Penanganan</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Waktu Kejadian</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($alerts as $alert)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm">
                        @if($alert['type'] === 'Threshold Breach')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                <span class="inline-block w-2 h-2 bg-red-600 rounded-full mr-2"></span>
                                {{ $alert['type'] }}
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-orange-100 text-orange-800">
                                <span class="inline-block w-2 h-2 bg-orange-600 rounded-full mr-2"></span>
                                {{ $alert['type'] }}
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-800 font-semibold">{{ $alert['device'] }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $alert['message'] }}</td>
                    <td class="px-6 py-4 text-sm text-gray-800">
                        @if($alert['value'])
                            <span class="font-mono">{{ $alert['value'] }} (Batas: {{ $alert['threshold'] }})</span>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <select onchange="updateAlertStatus({{ $alert['id'] }}, this.value)" class="px-3 py-1 border rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="pending" @selected($alert['status'] === 'pending')>Belum Ditangani</option>
                            <option value="checking" @selected($alert['status'] === 'checking')>Sedang Dicek</option>
                            <option value="resolved" @selected($alert['status'] === 'resolved')>Selesai</option>
                        </select>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $alert['created_at'] }}</td>
                    <td class="px-6 py-4 text-sm">
                        <button class="text-blue-600 hover:text-blue-800 font-semibold">Detail</button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-red-50 rounded-lg p-4">
            <p class="text-gray-600 text-sm">Total Alert Aktif</p>
            <p class="text-2xl font-bold text-red-600">{{ $alerts->where('status', 'active')->count() }}</p>
        </div>
        <div class="bg-yellow-50 rounded-lg p-4">
            <p class="text-gray-600 text-sm">Sedang Ditangani</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $alerts->where('status', 'checking')->count() }}</p>
        </div>
        <div class="bg-green-50 rounded-lg p-4">
            <p class="text-gray-600 text-sm">Sudah Selesai</p>
            <p class="text-2xl font-bold text-green-600">{{ $alerts->where('status', 'resolved')->count() }}</p>
        </div>
    </div>
</div>

<script>
function updateAlertStatus(id, status) {
    fetch(`/alerts/${id}/status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({ status: status })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Alert status updated:', data);
    });
}
</script>
@endsection
