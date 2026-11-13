<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    public function index(): View
    {
        return view('dashboard.settings.index');
    }

    public function users(): View
    {
        $users = [
            [
                'id' => 1,
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'role' => 'Super Admin',
                'status' => 'active',
                'created_at' => '2026-01-15',
            ],
            [
                'id' => 2,
                'name' => 'Admin Lokasi A',
                'email' => 'admin.a@example.com',
                'role' => 'Admin',
                'status' => 'active',
                'assigned_locations' => ['Lokasi A'],
                'created_at' => '2026-02-01',
            ],
        ];

        return view('dashboard.settings.users', ['users' => $users]);
    }

    public function systemSettings(): View
    {
        $settings = [
            'timezone' => 'Asia/Jakarta',
            'data_retention_days' => 90,
            'offline_timeout_minutes' => 15,
            'beacon_interval_minutes' => 5,
            'mqtt_broker_url' => 'mqtt://broker.example.com',
            'mqtt_username' => 'admin',
        ];

        return view('dashboard.settings.system', ['settings' => $settings]);
    }

    public function updateSystemSettings(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'System settings updated successfully',
        ]);
    }

    public function auditLog(): View
    {
        $logs = [
            [
                'id' => 1,
                'user' => 'Super Admin',
                'action' => 'Created Device',
                'details' => 'Created new device DEVICE_001',
                'timestamp' => '2026-11-14 09:30:00',
            ],
            [
                'id' => 2,
                'user' => 'Admin Lokasi A',
                'action' => 'Updated Threshold',
                'details' => 'Updated temperature threshold: 35°C',
                'timestamp' => '2026-11-14 09:15:00',
            ],
            [
                'id' => 3,
                'user' => 'Super Admin',
                'action' => 'Assigned Scope',
                'details' => 'Assigned Lokasi A to Admin Lokasi A',
                'timestamp' => '2026-11-13 14:20:00',
            ],
        ];

        return view('dashboard.settings.audit', ['logs' => $logs]);
    }

    public function scopeAssignment(): View
    {
        $admins = [
            [
                'id' => 2,
                'name' => 'Admin Lokasi A',
                'email' => 'admin.a@example.com',
                'assigned_locations' => ['Lokasi A'],
                'assigned_devices' => ['DEVICE_001', 'DEVICE_002'],
            ],
        ];

        $allLocations = ['Lokasi A', 'Lokasi B', 'Lokasi C'];
        $allDevices = [
            ['id' => 1, 'name' => 'Node Sensor 01', 'location' => 'Lokasi A'],
            ['id' => 2, 'name' => 'Node Sensor 02', 'location' => 'Lokasi B'],
            ['id' => 3, 'name' => 'Node Sensor 03', 'location' => 'Lokasi C'],
        ];

        return view('dashboard.settings.scope', [
            'admins' => $admins,
            'allLocations' => $allLocations,
            'allDevices' => $allDevices,
        ]);
    }

    public function updateScope(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Scope assignment updated successfully',
        ]);
    }
}
