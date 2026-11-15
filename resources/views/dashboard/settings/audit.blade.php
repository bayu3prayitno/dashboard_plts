@extends('layouts.app')

@section('title', 'Audit Log - IoT Dashboard')
@section('page-title', 'Audit Log')
@section('page-subtitle', 'Catatan lengkap aktivitas dan perubahan sistem')

@section('content')
<div class="space-y-6">
    <!-- Filter -->
    <form method="GET" action="{{ route('settings.auditLog') }}" class="flex flex-col md:flex-row gap-3">
        <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Cari aksi atau objek..." class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 flex-1">
        <select name="user_id" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Semua User</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}" @selected((string) $filters['user_id'] === (string) $user->id)>{{ $user->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Cari</button>
    </form>

    <!-- Audit Log Table -->
    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">User</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Aksi</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Rincian</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Waktu</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($logs as $log)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm font-semibold text-gray-800">{{ $log->user?->name ?? 'Sistem' }}</td>
                    <td class="px-6 py-4 text-sm">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 font-mono">
                            {{ $log->action }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $log->details() }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">{{ $log->created_at->timezone(\App\Http\Controllers\DashboardController::TZ)->format('d M Y H:i:s') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">Belum ada aktivitas yang tercatat.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $logs->links() }}</div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-gray-600 text-sm">Total Aktivitas</p>
            <p class="text-2xl font-bold text-gray-800">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-gray-600 text-sm">Hari Ini</p>
            <p class="text-2xl font-bold text-blue-600">{{ $stats['today'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-gray-600 text-sm">Minggu Ini</p>
            <p class="text-2xl font-bold text-green-600">{{ $stats['week'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-gray-600 text-sm">Bulan Ini</p>
            <p class="text-2xl font-bold text-purple-600">{{ $stats['month'] }}</p>
        </div>
    </div>

    <!-- Info Box -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <h4 class="font-semibold text-blue-900 mb-2">📋 Catatan Audit Log</h4>
        <ul class="text-sm text-blue-800 space-y-1">
            <li>• Perubahan perangkat, gateway, ambang batas, dan pengaturan dicatat otomatis beserta nilai sebelum dan sesudahnya</li>
            <li>• Kolom "Sistem" berarti perubahan dilakukan oleh sistem, bukan oleh pengguna</li>
        </ul>
    </div>
</div>
@endsection
