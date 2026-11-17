@extends('layouts.app')

@use('App\Support\Format')

@section('title', 'Manajemen Gateway - IoT Dashboard')
@section('page-title', 'Manajemen Gateway')
@section('page-subtitle', 'Monitor kesehatan dan status infrastruktur Gateway')

@php
    // Modal: animasi masuk/keluar CSS murni (Tailwind v4: starting: + transition-discrete), sama dengan halaman profil
    $dialog = 'm-auto w-[calc(100%-2rem)] max-w-md rounded-3xl bg-white p-6 text-ink shadow-2xl
               opacity-0 scale-95 transition-all transition-discrete duration-200 ease-out motion-reduce:transition-none
               open:opacity-100 open:scale-100 starting:open:opacity-0 starting:open:scale-95
               backdrop:bg-black/50 backdrop:opacity-0 backdrop:transition-all backdrop:transition-discrete backdrop:duration-200
               open:backdrop:opacity-100 starting:open:backdrop:opacity-0';
    // Buka modal otomatis bila validasi gagal atau datang dari /gateway/create
    $openCreate = $errors->createGateway->any() || request()->boolean('create');
@endphp

@section('content')
<div class="space-y-6">
    <!-- Action Bar -->
    <div class="flex justify-end">
        <button type="button" data-open-modal="create-gateway" class="btn btn-primary">Tambah Gateway</button>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="card">
            <p class="text-muted text-sm">Total Gateway</p>
            <p class="text-3xl font-bold text-ink">{{ $gateways->count() }}</p>
        </div>
        <div class="card">
            <p class="text-muted text-sm">Gateway Online</p>
            <p class="text-3xl font-bold text-lime-dark">{{ $gateways->where('status', 'online')->count() }}</p>
        </div>
        <div class="card">
            <p class="text-muted text-sm">Gateway Offline</p>
            <p class="text-3xl font-bold text-red-600">{{ $gateways->where('status', 'offline')->count() }}</p>
        </div>
        <div class="card">
            <p class="text-muted text-sm">Total Node</p>
            <p class="text-3xl font-bold text-primary">{{ $gateways->sum('devices_count') }}</p>
        </div>
    </div>

    <!-- Gateways Table -->
    <div class="card overflow-hidden p-0">
        <table class="w-full">
            <thead class="bg-canvas/60 border-b border-line">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-ink">Nama Gateway</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-ink">Kode</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-ink">Status Koneksi</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-ink">MQTT</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-ink">Internet</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-ink">Jumlah Node</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-ink">Komunikasi Terakhir</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-ink">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse($gateways as $gateway)
                <tr class="hover:bg-canvas/60">
                    <td class="px-6 py-4 text-sm font-semibold text-ink">{{ $gateway->name }}</td>
                    <td class="px-6 py-4 text-sm font-mono text-muted">{{ $gateway->gateway_code }}</td>
                    <td class="px-6 py-4 text-center"><x-status-badge :status="$gateway->status" /></td>
                    <td class="px-6 py-4 text-center"><x-status-badge :status="$gateway->mqtt_status" /></td>
                    <td class="px-6 py-4 text-center"><x-status-badge :status="$gateway->internet_status" /></td>
                    <td class="px-6 py-4 text-center text-sm font-semibold text-ink">{{ $gateway->devices_count }}</td>
                    <td class="px-6 py-4 text-sm text-muted">{{ Format::relTime($gateway->last_seen_at) }}</td>
                    <td class="px-6 py-4 text-sm">
                        <a href="{{ route('gateway.show', $gateway) }}" class="text-primary hover:underline font-semibold">Detail</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-4 text-center text-sm text-muted">Belum ada gateway terdaftar.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ================= MODAL: TAMBAH GATEWAY ================= --}}
<dialog id="create-gateway" class="{{ $dialog }}" aria-labelledby="create-gateway-title">
    <form method="POST" action="{{ route('gateway.store') }}" class="space-y-4">
        @csrf
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 id="create-gateway-title" class="text-lg font-semibold">Tambah Gateway</h2>
                <p class="mt-0.5 text-sm text-muted">Kode gateway dibuat otomatis (contoh: GW-001) dan tidak bisa diubah setelah disimpan.</p>
            </div>
            <button type="button" data-close-modal aria-label="Tutup" class="grid size-9 shrink-0 place-items-center rounded-full text-muted hover:bg-canvas hover:text-ink">
                <x-icon name="x" :size="18" />
            </button>
        </div>

        <label class="block text-sm">
            <span class="mb-1.5 block font-medium">Nama gateway</span>
            <input name="name" value="{{ old('name') }}" required maxlength="255" autofocus
                class="w-full rounded-xl border border-line bg-white px-3 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 @error('name', 'createGateway') border-red-300 @enderror">
            @error('name', 'createGateway')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
        </label>

        <div class="flex justify-end gap-2 pt-2">
            <button type="button" data-close-modal class="btn btn-outline">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </form>
</dialog>
@endsection

@push('scripts')
<script>
(function () {
    const dialog = document.getElementById('create-gateway');

    document.querySelectorAll('[data-open-modal="create-gateway"]').forEach((btn) =>
        btn.addEventListener('click', () => dialog.showModal()));
    dialog.querySelectorAll('[data-close-modal]').forEach((btn) => btn.addEventListener('click', () => dialog.close()));
    // Klik di backdrop (di luar panel) menutup modal
    dialog.addEventListener('click', (e) => {
        const r = dialog.getBoundingClientRect();
        if (e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom) dialog.close();
    });
    // Cegah kirim ganda
    dialog.querySelector('form').addEventListener('submit', (e) => {
        e.submitter?.setAttribute('disabled', '');
    });

    @if ($openCreate)
        dialog.showModal();
    @endif
})();
</script>
@endpush
