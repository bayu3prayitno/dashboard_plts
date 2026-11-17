@extends('layouts.app')

@section('title', 'Manajemen User - IoT Dashboard')
@section('page-title', 'Manajemen User')
@section('page-subtitle', 'Kelola akun pengguna dan role access')

@section('content')
<div class="space-y-6">
    <!-- Add User Button -->
    <div class="flex justify-end">
        <button onclick="openAddUserModal()" class="btn btn-primary flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Tambah User
        </button>
    </div>

    <!-- Users Table -->
    <div class="card overflow-hidden p-0">
        <table class="w-full">
            <thead class="bg-canvas/60 border-b border-line">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-ink">Nama</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-ink">Email</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-ink">Role</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-ink">Status</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-ink">Dibuat</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-ink">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse($users as $user)
                <tr class="hover:bg-canvas/60">
                    <td class="px-6 py-4 text-sm font-semibold text-ink">{{ $user->name }}</td>
                    <td class="px-6 py-4 text-sm text-muted">{{ $user->email }}</td>
                    <td class="px-6 py-4 text-sm">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium @if($user->isSuperAdmin()) bg-red-100 text-red-800 @else bg-primary-soft text-ink @endif">
                            {{ $user->roleLabel() }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        @if($user->is_active)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-primary-soft text-lime-dark">Aktif</span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-canvas text-ink">Nonaktif</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-muted">{{ $user->created_at->format('Y-m-d') }}</td>
                    <td class="px-6 py-4 text-sm">
                        <button class="text-primary hover:underline font-semibold mr-3">Edit</button>
                        <button class="text-red-600 hover:text-red-800 font-semibold">Hapus</button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-sm text-muted">Belum ada user.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Information -->
    <div class="bg-primary-soft border border-lime/40 rounded-lg p-6">
        <h4 class="font-semibold text-ink mb-2">ℹ️ Informasi Role</h4>
        <div class="space-y-2 text-sm text-ink">
            <p><strong>Super Admin:</strong> Akses penuh ke semua sistem, manajemen user, dan global settings</p>
            <p><strong>Admin:</strong> Akses terbatas sesuai lokasi/perangkat yang ditugaskan</p>
        </div>
    </div>
</div>
@endsection
