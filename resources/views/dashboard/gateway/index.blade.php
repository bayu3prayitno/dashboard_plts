@extends('layouts.app')

@use('App\Support\Format')

@section('title', 'Manajemen Gateway - IoT Dashboard')
@section('page-title', 'Manajemen Gateway')
@section('page-subtitle', 'Monitor kesehatan dan status infrastruktur Gateway')

@section('content')
<div class="space-y-6">
    <!-- Action Bar -->
    <div class="flex justify-end">
        <a href="{{ route('gateway.create') }}" class="btn btn-primary">Tambah Gateway</a>
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
@endsection
