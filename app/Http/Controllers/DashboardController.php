<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $data = [
            'total_devices' => 12,
            'online_devices' => 10,
            'offline_devices' => 2,
            'gateway_status' => 'connected',
            'mqtt_status' => 'connected',
            'last_update' => now()->format('Y-m-d H:i:s'),
            'latest_sensor_data' => [
                'temperature' => 28.5,
                'voltage' => 220.5,
                'current' => 5.2,
                'power' => 1146.0,
                'irradiance' => 850.0,
            ],
            'active_alerts' => 3,
        ];

        return view('dashboard.index', $data);
    }
}
