@extends('layouts.app')

@section('title', 'Manajemen Perangkat - IoT Dashboard')
@section('page-title', 'Manajemen Perangkat')
@section('page-subtitle', 'Kelola semua node sensor dan perangkat monitoring')

@section('content')
<div class="space-y-6">
    <!-- Action Bar -->
    <div class="flex justify-between items-center">
        <div class="flex gap-3">
            <input type="text" placeholder="Cari perangkat..." class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <select class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option>Semua Status</option>
                <option>Online</option>
                <option>Offline</option>
                <option>Nonaktif</option>
            </select>
        </div>
        <a href="{{ route('devices.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Tambah Perangkat
        </a>
    </div>

    <!-- Devices Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">ID Perangkat</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Nama Perangkat</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Lokasi</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Gateway</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Update Terakhir</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($devices as $device)
                @php $status = $device->status(); @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm text-gray-800 font-mono">DEVICE_{{ str_pad($device->node_id, 3, '0', STR_PAD_LEFT) }}</td>
                    <td class="px-6 py-4 text-sm text-gray-800">{{ $device->name }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $device->location }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $device->gateway?->name ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm">
                        @if($status === 'online')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                <span class="inline-block w-2 h-2 bg-green-600 rounded-full mr-2 animate-pulse"></span>
                                Online
                            </span>
                        @elseif($status === 'offline')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                <span class="inline-block w-2 h-2 bg-red-600 rounded-full mr-2"></span>
                                Offline
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                <span class="inline-block w-2 h-2 bg-gray-600 rounded-full mr-2"></span>
                                Nonaktif
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $device->last_seen_at?->diffForHumans() ?? 'Belum ada data' }}</td>
                    <td class="px-6 py-4 text-sm">
                        <div class="flex gap-2">
                            <a href="{{ route('devices.show', $device) }}" class="text-blue-600 hover:text-blue-800 font-semibold">Lihat</a>
                            <a href="{{ route('devices.edit', $device) }}" class="text-yellow-600 hover:text-yellow-800 font-semibold">Edit</a>
                            <button onclick="deleteDevice({{ $device->id }})" class="text-red-600 hover:text-red-800 font-semibold">Hapus</button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">Belum ada perangkat terdaftar.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="flex justify-between items-center text-sm text-gray-600">
        <span>Menampilkan {{ $devices->count() }} dari {{ $devices->total() }} perangkat</span>
        {{ $devices->links() }}
    </div>
</div>

<script>
async function deleteDevice(id) {
    if (!confirm('Apakah Anda yakin ingin menghapus perangkat ini?')) return;

    const res = await fetch(`/devices/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
    });
    const data = await res.json();
    showToast(data.message, data.success ? 'success' : 'error');
    if (data.success) setTimeout(() => window.location.reload(), 1000);
}
</script>
@endsection
