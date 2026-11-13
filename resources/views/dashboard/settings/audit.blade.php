@extends('layouts.app')

@section('title', 'Audit Log - IoT Dashboard')
@section('page-title', 'Audit Log')
@section('page-subtitle', 'Catatan lengkap aktivitas dan perubahan sistem')

@section('content')
<div class="space-y-6">
    <!-- Filter -->
    <div class="flex gap-3">
        <input type="text" placeholder="Cari aktivitas..." class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 flex-1">
        <select class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option>Semua User</option>
            <option>Super Admin</option>
            <option>Admin</option>
        </select>
    </div>

    <!-- Audit Log Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">User</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Action</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Details</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Timestamp</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">IP Address</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($logs as $log)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm font-semibold text-gray-800">{{ $log['user'] }}</td>
                    <td class="px-6 py-4 text-sm">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                            {{ $log['action'] }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $log['details'] }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $log['timestamp'] }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600 font-mono">192.168.1.100</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-gray-600 text-sm">Total Activities</p>
            <p class="text-2xl font-bold text-gray-800">{{ count($logs) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-gray-600 text-sm">Today</p>
            <p class="text-2xl font-bold text-blue-600">12</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-gray-600 text-sm">This Week</p>
            <p class="text-2xl font-bold text-green-600">45</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-gray-600 text-sm">This Month</p>
            <p class="text-2xl font-bold text-purple-600">156</p>
        </div>
    </div>

    <!-- Info Box -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <h4 class="font-semibold text-blue-900 mb-2">📋 Catatan Audit Log</h4>
        <ul class="text-sm text-blue-800 space-y-1">
            <li>• Semua aktivitas penting dicatat otomatis untuk compliance dan security audit</li>
            <li>• Log disimpan minimal 90 hari sesuai retention policy</li>
            <li>• IP Address mencerminkan lokasi pengguna yang melakukan action</li>
        </ul>
    </div>
</div>
@endsection
