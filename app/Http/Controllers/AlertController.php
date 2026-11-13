<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class AlertController extends Controller
{
    public function index(): View
    {
        $alerts = [
            [
                'id' => 1,
                'type' => 'Threshold Breach',
                'device' => 'Node Sensor 01',
                'message' => 'Temperature exceeds maximum threshold',
                'value' => 32.5,
                'threshold' => 30.0,
                'status' => 'active',
                'created_at' => '2 jam lalu',
            ],
            [
                'id' => 2,
                'type' => 'Device Offline',
                'device' => 'Node Sensor 03',
                'message' => 'Device offline for 35 minutes',
                'value' => null,
                'threshold' => null,
                'status' => 'active',
                'created_at' => '35 menit lalu',
            ],
            [
                'id' => 3,
                'type' => 'Threshold Breach',
                'device' => 'Node Sensor 02',
                'message' => 'Current exceeds maximum threshold',
                'value' => 8.5,
                'threshold' => 8.0,
                'status' => 'resolved',
                'created_at' => '4 jam lalu',
            ],
        ];

        return view('dashboard.alerts.index', ['alerts' => $alerts]);
    }

    public function updateStatus(int $id): JsonResponse
    {
        $statuses = ['pending', 'checking', 'resolved'];
        return response()->json([
            'success' => true,
            'message' => 'Alert status updated successfully',
            'new_status' => $statuses[array_rand($statuses)],
        ]);
    }

    public function configuration(): View
    {
        $thresholds = [
            [
                'parameter' => 'Temperature',
                'unit' => '°C',
                'min' => 15.0,
                'max' => 35.0,
            ],
            [
                'parameter' => 'Voltage',
                'unit' => 'V',
                'min' => 190.0,
                'max' => 240.0,
            ],
            [
                'parameter' => 'Current',
                'unit' => 'A',
                'min' => 0.0,
                'max' => 10.0,
            ],
            [
                'parameter' => 'Power',
                'unit' => 'W',
                'min' => 0.0,
                'max' => 2500.0,
            ],
            [
                'parameter' => 'Irradiance',
                'unit' => 'W/m²',
                'min' => 0.0,
                'max' => 1200.0,
            ],
        ];

        return view('dashboard.alerts.configuration', [
            'thresholds' => $thresholds,
            'offline_timeout' => 15,
        ]);
    }

    public function updateThreshold(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Threshold updated successfully',
        ]);
    }
}
