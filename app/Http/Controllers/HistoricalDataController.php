<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HistoricalDataController extends Controller
{
    public function index(): View
    {
        $devices = [
            ['id' => 1, 'name' => 'Node Sensor 01'],
            ['id' => 2, 'name' => 'Node Sensor 02'],
            ['id' => 3, 'name' => 'Node Sensor 03'],
        ];

        $sensors = [
            'temperature' => 'Temperature (°C)',
            'voltage' => 'Voltage (V)',
            'current' => 'Current (A)',
            'power' => 'Power (W)',
            'irradiance' => 'Irradiance (W/m²)',
        ];

        $data = [
            [
                'timestamp' => '2026-11-14 10:00:00',
                'packet_number' => 1001,
                'device_id' => 'DEVICE_001',
                'device_name' => 'Node Sensor 01',
                'temperature' => 28.5,
                'voltage' => 220.5,
                'current' => 5.2,
                'power' => 1146.0,
                'irradiance' => 850.0,
            ],
            [
                'timestamp' => '2026-11-14 10:05:00',
                'packet_number' => 1002,
                'device_id' => 'DEVICE_001',
                'device_name' => 'Node Sensor 01',
                'temperature' => 28.7,
                'voltage' => 220.3,
                'current' => 5.3,
                'power' => 1167.0,
                'irradiance' => 855.0,
            ],
            [
                'timestamp' => '2026-11-14 10:10:00',
                'packet_number' => 1003,
                'device_id' => 'DEVICE_001',
                'device_name' => 'Node Sensor 01',
                'temperature' => 28.9,
                'voltage' => 220.2,
                'current' => 5.4,
                'power' => 1188.0,
                'irradiance' => 860.0,
            ],
        ];

        return view('dashboard.historical.index', [
            'devices' => $devices,
            'sensors' => $sensors,
            'data' => $data,
        ]);
    }

    public function filter(): JsonResponse
    {
        $data = [
            [
                'timestamp' => '2026-11-14 10:00:00',
                'packet_number' => 1001,
                'device_name' => 'Node Sensor 01',
                'temperature' => 28.5,
                'voltage' => 220.5,
                'current' => 5.2,
                'power' => 1146.0,
                'irradiance' => 850.0,
            ],
        ];

        return response()->json($data);
    }

    public function exportCsv(): StreamedResponse
    {
        $csv = "Timestamp,Packet Number,Device ID,Temperature (°C),Voltage (V),Current (A),Power (W),Irradiance (W/m²)\n";
        $csv .= "2026-11-14 10:00:00,1001,DEVICE_001,28.5,220.5,5.2,1146.0,850.0\n";
        $csv .= "2026-11-14 10:05:00,1002,DEVICE_001,28.7,220.3,5.3,1167.0,855.0\n";
        $csv .= "2026-11-14 10:10:00,1003,DEVICE_001,28.9,220.2,5.4,1188.0,860.0\n";

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, 'sensor_data_' . date('Y-m-d_H-i-s') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function getChartData(): JsonResponse
    {
        $data = [
            'labels' => ['10:00', '10:05', '10:10', '10:15', '10:20'],
            'datasets' => [
                [
                    'label' => 'Temperature (°C)',
                    'data' => [28.5, 28.7, 28.9, 29.1, 28.8],
                    'borderColor' => '#ef4444',
                ],
                [
                    'label' => 'Voltage (V)',
                    'data' => [220.5, 220.3, 220.2, 220.1, 220.4],
                    'borderColor' => '#3b82f6',
                ],
            ],
        ];

        return response()->json($data);
    }
}
