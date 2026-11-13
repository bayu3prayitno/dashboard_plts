<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class GatewayController extends Controller
{
    public function index(): View
    {
        $gateways = [
            [
                'id' => 1,
                'name' => 'Gateway 01',
                'location' => 'Lokasi A',
                'status' => 'connected',
                'mqtt_status' => 'connected',
                'internet_status' => 'connected',
                'last_communication' => '1 menit lalu',
                'connected_nodes' => 2,
                'sd_card_status' => 'healthy',
                'sd_card_usage' => '45%',
            ],
            [
                'id' => 2,
                'name' => 'Gateway 02',
                'location' => 'Lokasi B',
                'status' => 'connected',
                'mqtt_status' => 'connected',
                'internet_status' => 'connected',
                'last_communication' => '3 menit lalu',
                'connected_nodes' => 1,
                'sd_card_status' => 'healthy',
                'sd_card_usage' => '62%',
            ],
        ];

        return view('dashboard.gateway.index', ['gateways' => $gateways]);
    }

    public function show(int $id): View
    {
        $gateway = [
            'id' => $id,
            'name' => 'Gateway 01',
            'location' => 'Lokasi A',
            'device_id' => 'GATEWAY_001',
            'firmware_version' => '1.0.1',
            'status' => 'connected',
            'infrastructure_health' => [
                'gateway_connection' => 'connected',
                'mqtt_broker' => 'connected',
                'internet_connection' => 'connected',
                'sd_card' => 'healthy',
            ],
            'last_communication' => now()->format('Y-m-d H:i:s'),
            'connected_nodes' => ['DEVICE_001', 'DEVICE_002'],
            'beacon_interval' => '5 menit',
        ];

        return view('dashboard.gateway.show', ['gateway' => $gateway]);
    }
}
