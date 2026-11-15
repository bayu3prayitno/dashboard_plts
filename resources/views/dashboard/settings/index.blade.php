@extends('layouts.app')

@section('title', 'Pengaturan Sistem - IoT Dashboard')
@section('page-title', 'Pengaturan Sistem')
@section('page-subtitle', 'Manajemen konfigurasi dan administration')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <!-- User Management -->
    <a href="{{ route('settings.users') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
        <div class="flex items-center mb-4">
            <div class="bg-blue-100 rounded-lg p-3">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 8.048M3 12h18M8 12a4 4 0 11-2.828-4 4 4 0 012.828 4m10 0a4 4 0 11-2.828-4 4 4 0 012.828 4"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 ml-3">Manajemen User</h3>
        </div>
        <p class="text-gray-600 text-sm">Kelola akun pengguna, role, dan pembagian akses</p>
        <div class="mt-4 text-blue-600 font-semibold text-sm">→ Buka</div>
    </a>

    <!-- System Settings -->
    <a href="{{ route('settings.system') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
        <div class="flex items-center mb-4">
            <div class="bg-yellow-100 rounded-lg p-3">
                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 ml-3">Pengaturan Sistem</h3>
        </div>
        <p class="text-gray-600 text-sm">Konfigurasi timezone, data retention, dan offline timeout</p>
        <div class="mt-4 text-blue-600 font-semibold text-sm">→ Buka</div>
    </a>

    <!-- Alert Configuration -->
    <a href="{{ route('alerts.configuration') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
        <div class="flex items-center mb-4">
            <div class="bg-red-100 rounded-lg p-3">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 ml-3">Konfigurasi Alert</h3>
        </div>
        <p class="text-gray-600 text-sm">Atur threshold sensor dan offline timeout</p>
        <div class="mt-4 text-blue-600 font-semibold text-sm">→ Buka</div>
    </a>

    <!-- Scope Assignment -->
    <a href="{{ route('settings.scope') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
        <div class="flex items-center mb-4">
            <div class="bg-green-100 rounded-lg p-3">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.856-1.487M15 10a3 3 0 11-6 0 3 3 0 016 0zM6 20h12a6 6 0 016-6H0a6 6 0 016 6z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 ml-3">Penugasan Wewenang</h3>
        </div>
        <p class="text-gray-600 text-sm">Assign lokasi dan device ke Admin tertentu</p>
        <div class="mt-4 text-blue-600 font-semibold text-sm">→ Buka</div>
    </a>

    <!-- Audit Log -->
    <a href="{{ route('settings.auditLog') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
        <div class="flex items-center mb-4">
            <div class="bg-purple-100 rounded-lg p-3">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 ml-3">Audit Log</h3>
        </div>
        <p class="text-gray-600 text-sm">Lihat catatan aktivitas dan perubahan sistem</p>
        <div class="mt-4 text-blue-600 font-semibold text-sm">→ Buka</div>
    </a>

    <!-- System Health -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center mb-4">
            <div class="bg-cyan-100 rounded-lg p-3">
                <svg class="w-6 h-6 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 ml-3">Kesehatan Sistem</h3>
        </div>
        <div class="space-y-2">
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Database</span>
                <span class="inline-block px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded">OK</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Storage</span>
                <span class="inline-block px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded">OK</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">API</span>
                <span class="inline-block px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded">OK</span>
            </div>
        </div>
    </div>
</div>

<!-- System Information -->
<div class="mt-6 bg-white rounded-lg shadow p-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Informasi Sistem</h3>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <p class="text-gray-600 text-sm">Versi Dashboard</p>
            <p class="text-xl font-bold text-gray-800">1.0.0</p>
        </div>
        <div>
            <p class="text-gray-600 text-sm">Total User</p>
            <p class="text-xl font-bold text-gray-800">5</p>
        </div>
        <div>
            <p class="text-gray-600 text-sm">Total Device</p>
            <p class="text-xl font-bold text-gray-800">12</p>
        </div>
        <div>
            <p class="text-gray-600 text-sm">Uptime</p>
            <p class="text-xl font-bold text-gray-800">45 hari</p>
        </div>
    </div>
</div>
@endsection
