@extends('layouts.app')

@section('title', 'Manajemen User - IoT Dashboard')
@section('page-title', 'Manajemen User')
@section('page-subtitle', 'Kelola akun pengguna dan role access')

@section('content')
<div class="space-y-6">
    <!-- Add User Button -->
    <div class="flex justify-end">
        <button onclick="openAddUserModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Tambah User
        </button>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Nama</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Email</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Role</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Dibuat</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($users as $user)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm font-semibold text-gray-800">{{ $user->name }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $user->email }}</td>
                    <td class="px-6 py-4 text-sm">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium @if($user->isSuperAdmin()) bg-red-100 text-red-800 @else bg-blue-100 text-blue-800 @endif">
                            {{ $user->roleLabel() }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        @if($user->is_active)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">Aktif</span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">Nonaktif</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $user->created_at->format('Y-m-d') }}</td>
                    <td class="px-6 py-4 text-sm">
                        <button class="text-blue-600 hover:text-blue-800 font-semibold mr-3">Edit</button>
                        <button class="text-red-600 hover:text-red-800 font-semibold">Hapus</button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">Belum ada user.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Information -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <h4 class="font-semibold text-blue-900 mb-2">ℹ️ Informasi Role</h4>
        <div class="space-y-2 text-sm text-blue-800">
            <p><strong>Super Admin:</strong> Akses penuh ke semua sistem, manajemen user, dan global settings</p>
            <p><strong>Admin:</strong> Akses terbatas sesuai lokasi/perangkat yang ditugaskan</p>
        </div>
    </div>
</div>
@endsection
