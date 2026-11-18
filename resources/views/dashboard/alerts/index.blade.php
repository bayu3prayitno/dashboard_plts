@extends('layouts.app')

@section('title', 'Manajemen Peringatan - IoT Dashboard')
@section('page-title', 'Manajemen Peringatan')
@section('page-subtitle', 'Monitor dan kelola semua peringatan sistem')

@section('content')
<div class="space-y-6">
    <!-- Filter Bar -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-3 card">
        <form method="GET" action="{{ route('alerts.index') }}" class="flex flex-wrap gap-3">
            <select name="status" onchange="this.form.submit()" class="px-4 py-2 rounded-full border border-line bg-white outline-none focus:ring-2 focus:ring-primary/15 focus:border-primary">
                <option value="">Semua Status</option>
                <option value="new" @selected($filters['status'] === 'new')>Baru</option>
                <option value="acknowledged" @selected($filters['status'] === 'acknowledged')>Ditangani</option>
                <option value="resolved" @selected($filters['status'] === 'resolved')>Selesai</option>
            </select>
            <select name="type" onchange="this.form.submit()" class="px-4 py-2 rounded-full border border-line bg-white outline-none focus:ring-2 focus:ring-primary/15 focus:border-primary">
                <option value="">Semua Tipe</option>
                <option value="threshold" @selected($filters['type'] === 'threshold')>Threshold Breach</option>
                <option value="offline" @selected($filters['type'] === 'offline')>Device Offline</option>
            </select>
        </form>
        <a href="{{ route('alerts.configuration') }}" class="btn btn-primary flex items-center gap-2 self-start md:self-auto">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
            </svg>
            Konfigurasi Alert
        </a>
    </div>

    <!-- Alerts Table -->
    <div class="card overflow-x-auto">
        <table class="w-full">
            <thead class="bg-canvas/60 border-b border-line">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-ink">Tipe Alert</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-ink">Perangkat</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-ink">Pesan</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-ink">Waktu Kejadian</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-ink">Status Penanganan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse($alerts as $alert)
                <tr class="hover:bg-canvas/60">
                    <td class="px-6 py-4 text-sm">
                        @if($alert->alert_type === \App\Models\Alert::TYPE_OFFLINE)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-orange-100 text-orange-800">
                                <span class="inline-block w-2 h-2 bg-orange-600 rounded-full mr-2"></span>{{ $alert->typeLabel() }}
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                <span class="inline-block w-2 h-2 bg-red-600 rounded-full mr-2"></span>{{ $alert->typeLabel() }}
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-ink font-semibold">
                        {{ $alert->device?->name ?? '-' }}
                        <span class="block text-xs font-normal text-muted">Node {{ $alert->device?->node_id }}</span>
                    </td>
                    <td class="px-6 py-4 text-sm text-muted">{{ $alert->message() }}</td>
                    <td class="px-6 py-4 text-sm text-muted whitespace-nowrap">{{ $alert->triggered_at->timezone(\App\Http\Controllers\DashboardController::tz())->format('d M Y H:i') }}</td>
                    <td class="px-6 py-4 text-sm">
                        <select onchange="updateAlertStatus('{{ route('alerts.updateStatus', $alert) }}', this.value)" class="px-3 py-1 border rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-primary/15 focus:border-primary">
                            <option value="new" @selected($alert->status === 'new')>Baru</option>
                            <option value="acknowledged" @selected($alert->status === 'acknowledged')>Ditangani</option>
                            <option value="resolved" @selected($alert->status === 'resolved')>Selesai</option>
                        </select>
                        @if($alert->resolution_note)
                            <span class="block text-xs text-muted mt-1 hidden">{{ $alert->resolution_note }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-sm text-muted">Tidak ada alert untuk filter ini.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="flex flex-col sm:flex-row justify-between items-center gap-3 text-sm text-muted">
        <span>Menampilkan {{ $alerts->firstItem() ?? 0 }}–{{ $alerts->lastItem() ?? 0 }} dari {{ $alerts->total() }} alert</span>
        <x-pagination :paginator="$alerts" />
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 hidden">
        <div class="bg-red-50 rounded-lg p-4">
            <p class="text-muted text-sm">Alert Baru</p>
            <p class="text-2xl font-bold text-red-600">{{ $counts['new'] }}</p>
        </div>
        <div class="bg-yellow-50 rounded-lg p-4">
            <p class="text-muted text-sm">Sedang Ditangani</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $counts['acknowledged'] }}</p>
        </div>
        <div class="bg-green-50 rounded-lg p-4">
            <p class="text-muted text-sm">Sudah Selesai</p>
            <p class="text-2xl font-bold text-lime-dark">{{ $counts['resolved'] }}</p>
        </div>
    </div>
</div>

<script>
async function updateAlertStatus(url, status) {
    let note = null;
    if (status === 'resolved') {
        note = await confirmDialog({
            title: 'Tandai selesai?',
            message: 'Alert ini akan ditandai sudah selesai.',
            confirmText: 'Ya, selesaikan',
            tone: 'warning',
            input: { label: 'Catatan penyelesaian (opsional)', placeholder: 'Contoh: sensor sudah dikalibrasi ulang' },
        });
        if (note === false) {
            window.location.reload(); // dibatalkan: kembalikan status semula
            return;
        }
    }

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({ status: status, resolution_note: note })
    })
    .then(response => response.json())
    .then(data => {
        showToast(data.message, data.success ? 'success' : 'error');
        setTimeout(() => window.location.reload(), 600);
    })
    .catch(() => showToast('Gagal memperbarui status alert.', 'error'));
}
</script>
@endsection
