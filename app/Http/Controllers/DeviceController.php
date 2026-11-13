<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class DeviceController extends Controller
{
    public function index(): View
    {
        $devices = [
            [
                'id' => 1,
                'name' => 'Node Sensor 01',
                'location' => 'Lokasi A',
                'gateway' => 'Gateway 01',
                'status' => 'online',
                'last_update' => '2 menit lalu',
            ],
            [
                'id' => 2,
                'name' => 'Node Sensor 02',
                'location' => 'Lokasi B',
                'gateway' => 'Gateway 01',
                'status' => 'online',
                'last_update' => '5 menit lalu',
            ],
            [
                'id' => 3,
                'name' => 'Node Sensor 03',
                'location' => 'Lokasi C',
                'gateway' => 'Gateway 02',
                'status' => 'offline',
                'last_update' => '35 menit lalu',
            ],
        ];

        return view('dashboard.devices.index', ['devices' => $devices]);
    }

    public function show(int $id): View
    {
        $device = [
            'id' => $id,
            'name' => 'Node Sensor 01',
            'location' => 'Lokasi A',
            'gateway' => 'Gateway 01',
            'status' => 'online',
            'device_id' => 'DEVICE_001',
            'sensor_data' => [
                'temperature' => 28.5,
                'voltage' => 220.5,
                'current' => 5.2,
                'power' => 1146.0,
                'irradiance' => 850.0,
            ],
            'network_metrics' => [
                'rssi' => -85,
                'snr' => 10,
                'packet_loss' => 2.5,
            ],
            'last_update' => now()->format('Y-m-d H:i:s'),
        ];

        return view('dashboard.devices.show', ['device' => $device]);
    }

    public function create(): View
    {
        return view('dashboard.devices.create');
    }

    public function store(): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Device created successfully']);
    }

    public function edit(int $id): View
    {
        $device = [
            'id' => $id,
            'name' => 'Node Sensor 01',
            'location' => 'Lokasi A',
            'gateway' => 'Gateway 01',
        ];

        return view('dashboard.devices.edit', ['device' => $device]);
    }

    public function update(int $id): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Device updated successfully']);
    }

    public function delete(int $id): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Device deleted successfully']);
    }
}
