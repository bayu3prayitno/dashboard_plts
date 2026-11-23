@extends('layouts.app')

@section('title', 'Manajemen Perangkat - IoT Dashboard')
@section('page-title', 'Manajemen Perangkat')
@section('page-subtitle', 'Kelola semua node sensor dan perangkat monitoring')

@section('content')
<div class="space-y-6">
    <!-- Action Bar -->
    <form method="GET" action="{{ route('devices.index') }}" class="flex justify-between items-center gap-3">
        <div class="flex gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama/lokasi/node ID..."
                   class="px-4 py-2 rounded-full border border-line bg-white outline-none focus:ring-2 focus:ring-primary/15 focus:border-primary w-72">
            <select name="status" onchange="this.form.submit()"
                    class="px-4 py-2 rounded-full border border-line bg-white outline-none focus:ring-2 focus:ring-primary/15 focus:border-primary">
                <option value="">Semua Status</option>
                <option value="online" @selected(request('status') === 'online')>Online</option>
                <option value="offline" @selected(request('status') === 'offline')>Offline</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
            </select>
            <button type="submit" class="btn btn-outline flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                Cari
            </button>
            @if(request('search') || request('status'))
                <a href="{{ route('devices.index') }}" class="btn btn-outline text-muted">Reset</a>
            @endif
        </div>
        <a href="{{ route('devices.create') }}" class="btn btn-primary flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Tambah Perangkat
        </a>
    </form>

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
                        <div class="action-menu">
                            <button type="button" data-action-toggle aria-haspopup="true" aria-expanded="false" aria-label="Aksi untuk {{ $device->name }}"
                                    class="p-1.5 rounded-full text-muted hover:bg-canvas hover:text-ink focus:outline-none focus:ring-2 focus:ring-primary/15">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <circle cx="12" cy="5" r="1.75"></circle>
                                    <circle cx="12" cy="12" r="1.75"></circle>
                                    <circle cx="12" cy="19" r="1.75"></circle>
                                </svg>
                            </button>
                            <div data-action-panel role="menu"
                                 class="hidden fixed z-50 w-40 py-1 bg-white border border-line rounded-xl shadow-lg">
                                <a href="{{ route('devices.show', $device) }}" role="menuitem" class="block px-4 py-2 text-sm text-ink hover:bg-canvas">Lihat</a>
                                <a href="{{ route('devices.edit', $device) }}" role="menuitem" class="block px-4 py-2 text-sm text-ink hover:bg-canvas">Edit</a>
                                @if($device->is_active)
                                    <button type="button" role="menuitem" disabled title="Nonaktifkan perangkat terlebih dahulu" class="block w-full text-left px-4 py-2 text-sm text-gray-400 cursor-not-allowed">Hapus</button>
                                @else
                                    <button type="button" role="menuitem" onclick="deleteResource(@js(route('devices.delete', $device)), { title: 'Hapus perangkat?', confirmText: @js('Perangkat '.$device->name.' beserta seluruh riwayatnya akan dihapus permanen.') })" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">Hapus</button>
                                @endif
                            </div>
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
@endsection

@push('scripts')
<script>
(() => {
    let openPanel = null;

    const close = () => {
        if (!openPanel) return;
        openPanel.classList.add('hidden');
        openPanel.previousElementSibling?.setAttribute('aria-expanded', 'false');
        openPanel = null;
    };

    const open = (toggle, panel) => {
        close();
        panel.classList.remove('hidden');
        const r = toggle.getBoundingClientRect();
        const w = panel.offsetWidth, h = panel.offsetHeight;
        const top = r.bottom + 4 + h > window.innerHeight ? r.top - h - 4 : r.bottom + 4;
        panel.style.top = `${Math.max(8, top)}px`;
        panel.style.left = `${Math.max(8, r.right - w)}px`;
        toggle.setAttribute('aria-expanded', 'true');
        openPanel = panel;
    };

    document.addEventListener('click', (e) => {
        const toggle = e.target.closest('[data-action-toggle]');
        if (toggle) {
            const panel = toggle.nextElementSibling;
            panel === openPanel ? close() : open(toggle, panel);
            return;
        }
        if (e.target.closest('[data-action-panel] [role="menuitem"]') || !e.target.closest('[data-action-panel]')) close();
    });
    document.addEventListener('keydown', (e) => e.key === 'Escape' && close());
    window.addEventListener('scroll', close, true);
    window.addEventListener('resize', close);
})();
</script>
@endpush
