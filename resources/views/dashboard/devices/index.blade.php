@extends('layouts.app')

@section('title', 'Manajemen Perangkat - IoT Dashboard')
@section('page-title', 'Manajemen Perangkat')
@section('page-subtitle', 'Kelola semua node sensor dan perangkat monitoring')

@section('content')
<div class="space-y-6">
    <!-- Action Bar -->
    <div class="flex justify-between items-center">
        <div class="flex gap-3">
            <input type="text" placeholder="Cari perangkat..." class="px-4 py-2 rounded-full border border-line bg-white outline-none focus:ring-2 focus:ring-primary/15 focus:border-primary">
            <select class="px-4 py-2 rounded-full border border-line bg-white outline-none focus:ring-2 focus:ring-primary/15 focus:border-primary">
                <option>Semua Status</option>
                <option>Online</option>
                <option>Offline</option>
                <option>Nonaktif</option>
            </select>
        </div>
        <a href="{{ route('devices.create') }}" class="btn btn-primary flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Tambah Perangkat
        </a>
    </div>

    <!-- Devices Table -->
    <div class="card overflow-hidden p-0">
        <table class="w-full">
            <thead class="bg-canvas/60 border-b border-line">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-ink">ID Perangkat</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-ink">Nama Perangkat</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-ink">Lokasi</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-ink">Gateway</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-ink">Status</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-ink">Update Terakhir</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-ink">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse($devices as $device)
                @php $status = $device->status(); @endphp
                <tr class="hover:bg-canvas/60">
                    <td class="px-6 py-4 text-sm text-ink font-mono">DEVICE_{{ str_pad($device->node_id, 3, '0', STR_PAD_LEFT) }}</td>
                    <td class="px-6 py-4 text-sm text-ink">{{ $device->name }}</td>
                    <td class="px-6 py-4 text-sm text-muted">{{ $device->location }}</td>
                    <td class="px-6 py-4 text-sm text-muted">{{ $device->gateway?->name ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm">
                        @if($status === 'online')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-primary-soft text-lime-dark">
                                <span class="inline-block w-2 h-2 bg-green-600 rounded-full mr-2 animate-pulse"></span>
                                Online
                            </span>
                        @elseif($status === 'offline')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                <span class="inline-block w-2 h-2 bg-red-600 rounded-full mr-2"></span>
                                Offline
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-canvas text-ink">
                                <span class="inline-block w-2 h-2 bg-muted rounded-full mr-2"></span>
                                Nonaktif
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-muted">{{ $device->last_seen_at?->diffForHumans() ?? 'Belum ada data' }}</td>
                    <td class="px-6 py-4 text-sm">
                        <div class="flex gap-2">
                            <a href="{{ route('devices.show', $device) }}" class="text-primary hover:underline font-semibold">Lihat</a>
                            <a href="{{ route('devices.edit', $device) }}" class="text-yellow-600 hover:text-yellow-800 font-semibold">Edit</a>
                            <button onclick="deleteDevice({{ $device->id }})" class="text-red-600 hover:text-red-800 font-semibold">Hapus</button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-sm text-muted">Belum ada perangkat terdaftar.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="flex justify-between items-center text-sm text-muted">
        <span>Menampilkan {{ $devices->count() }} dari {{ $devices->total() }} perangkat</span>
        <x-pagination :paginator="$devices" />
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
