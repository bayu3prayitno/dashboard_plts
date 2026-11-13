@extends('layouts.app')

@section('title', 'Penugasan Wewenang - IoT Dashboard')
@section('page-title', 'Penugasan Wewenang (Scope Assignment)')
@section('page-subtitle', 'Assign lokasi dan perangkat ke Admin tertentu')

@section('content')
<div class="space-y-6">
    <!-- Add New Scope Assignment -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Assign Wewenang Baru</h3>

        <form method="POST" action="{{ route('settings.scopeUpdate', 0) }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Pilih Admin</label>
                <select name="admin_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="">-- Pilih Admin --</option>
                    <option value="2">Admin Lokasi A</option>
                    <option value="3">Admin Lokasi B</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Lokasi yang Dapat Diakses</label>
                <div class="space-y-2">
                    @foreach($allLocations as $location)
                    <div class="flex items-center">
                        <input type="checkbox" name="locations[]" value="{{ $location }}" class="mr-2">
                        <label class="text-gray-700">{{ $location }}</label>
                    </div>
                    @endforeach
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Perangkat yang Dapat Diakses</label>
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    @foreach($allDevices as $device)
                    <div class="flex items-center">
                        <input type="checkbox" name="devices[]" value="{{ $device['id'] }}" class="mr-2">
                        <label class="text-gray-700">{{ $device['name'] }} ({{ $device['location'] }})</label>
                    </div>
                    @endforeach
                </div>
            </div>

            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Simpan Penugasan</button>
        </form>
    </div>

    <!-- Current Assignments -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Penugasan Saat Ini</h3>

        <div class="space-y-4">
            @foreach($admins as $admin)
            <div class="p-4 border border-gray-200 rounded-lg">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="font-semibold text-gray-800">{{ $admin['name'] }}</p>
                        <p class="text-sm text-gray-600">{{ $admin['email'] }}</p>
                    </div>
                    <button onclick="editScope({{ $admin['id'] }})" class="text-blue-600 hover:text-blue-800 font-semibold">Edit</button>
                </div>

                <div class="grid grid-cols-2 gap-4 pt-3 border-t">
                    <div>
                        <p class="text-xs text-gray-600 font-semibold mb-2">LOKASI:</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($admin['assigned_locations'] as $location)
                            <span class="inline-block px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">{{ $location }}</span>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600 font-semibold mb-2">PERANGKAT:</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($admin['assigned_devices'] as $device)
                            <span class="inline-block px-2 py-1 bg-green-100 text-green-800 text-xs rounded">{{ $device }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Information Box -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <h4 class="font-semibold text-blue-900 mb-2">ℹ️ Cara Kerja Scope Assignment</h4>
        <ul class="text-sm text-blue-800 space-y-1">
            <li>• <strong>Lokasi:</strong> Admin hanya dapat melihat dan mengelola device dalam lokasi yang ditugaskan</li>
            <li>• <strong>Perangkat:</strong> Admin hanya dapat melihat data detail dari perangkat yang ditugaskan</li>
            <li>• <strong>Multiple Assignment:</strong> Satu Admin dapat mengelola multiple lokasi dan perangkat sekaligus</li>
            <li>• <strong>Super Admin:</strong> Selalu memiliki akses ke semua lokasi dan perangkat</li>
        </ul>
    </div>

    <!-- Example Scenario -->
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
        <h4 class="font-semibold text-gray-900 mb-2">📝 Contoh Scenario</h4>
        <div class="text-sm text-gray-700 space-y-2">
            <p><strong>Skenario:</strong> PT ABC memiliki 2 lokasi dengan 3 admin berbeda</p>
            <ul class="list-disc list-inside space-y-1 ml-2">
                <li>Super Admin: Akses penuh ke semua lokasi (A, B) dan perangkat</li>
                <li>Admin Lokasi A: Hanya bisa kelola Lokasi A dan Device 01, 02</li>
                <li>Admin Lokasi B: Hanya bisa kelola Lokasi B dan Device 03</li>
            </ul>
        </div>
    </div>
</div>

<script>
function editScope(id) {
    console.log('Edit scope for admin:', id);
    // Implement edit modal or navigation
}
</script>
@endsection
