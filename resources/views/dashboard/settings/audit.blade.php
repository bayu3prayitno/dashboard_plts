@extends('layouts.app')

@section('title', 'Audit Log - IoT Dashboard')
@section('page-title', 'Audit Log')
@section('page-subtitle', 'Catatan lengkap aktivitas dan perubahan sistem')

@section('content')
<div class="space-y-6">
    <!-- Filter -->
    <form method="GET" action="{{ route('settings.auditLog') }}" class="flex flex-col md:flex-row gap-3">
        <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Cari aksi atau objek..." class="px-4 py-2 rounded-full border border-line bg-white outline-none focus:ring-2 focus:ring-primary/15 focus:border-primary flex-1">
        <select name="user_id" onchange="this.form.submit()" class="px-4 py-2 rounded-full border border-line bg-white outline-none focus:ring-2 focus:ring-primary/15 focus:border-primary">
            <option value="">Semua User</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}" @selected((string) $filters['user_id'] === (string) $user->id)>{{ $user->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-primary">Cari</button>
    </form>

    <!-- Audit Log Table -->
    <div class="card overflow-x-auto">
        <table class="w-full">
            <thead class="bg-canvas/60 border-b border-line">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-ink">User</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-ink">Aksi</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-ink">Rincian</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-ink">Waktu</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse($logs as $log)
                <tr class="hover:bg-canvas/60">
                    <td class="px-6 py-4 text-sm font-semibold text-ink">{{ $log->user?->name ?? 'Sistem' }}</td>
                    <td class="px-6 py-4 text-sm">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-primary-soft text-ink font-mono">
                            {{ $log->action }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-muted">{{ $log->details() }}</td>
                    <td class="px-6 py-4 text-sm text-muted whitespace-nowrap">{{ $log->created_at->timezone(\App\Http\Controllers\DashboardController::tz())->format('d M Y H:i:s') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-8 text-center text-sm text-muted">Belum ada aktivitas yang tercatat.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $logs->links() }}</div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="card">
            <p class="text-muted text-sm">Total Aktivitas</p>
            <p class="text-2xl font-bold text-ink">{{ $stats['total'] }}</p>
        </div>
        <div class="card">
            <p class="text-muted text-sm">Hari Ini</p>
            <p class="text-2xl font-bold text-primary">{{ $stats['today'] }}</p>
        </div>
        <div class="card">
            <p class="text-muted text-sm">Minggu Ini</p>
            <p class="text-2xl font-bold text-lime-dark">{{ $stats['week'] }}</p>
        </div>
        <div class="card">
            <p class="text-muted text-sm">Bulan Ini</p>
            <p class="text-2xl font-bold text-purple-600">{{ $stats['month'] }}</p>
        </div>
    </div>

    <!-- Info Box -->
    <div class="bg-primary-soft border border-lime/40 rounded-lg p-6">
        <h4 class="font-semibold text-ink mb-2">📋 Catatan Audit Log</h4>
        <ul class="text-sm text-ink space-y-1">
            <li>• Perubahan perangkat, gateway, ambang batas, dan pengaturan dicatat otomatis beserta nilai sebelum dan sesudahnya</li>
            <li>• Kolom "Sistem" berarti perubahan dilakukan oleh sistem, bukan oleh pengguna</li>
        </ul>
    </div>
</div>
@endsection
