<?php

namespace App\Http\Controllers;

use Carbon\CarbonImmutable;
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
        // Data tiruan yang sama dengan Dashboard — ganti dengan Gateway::with('devices')->findOrFail($id)
        $now = CarbonImmutable::now(DashboardController::TZ);

        return view('dashboard.gateway.show', [
            'gateway' => DashboardController::mockGateway($now),
            'devices' => DashboardController::withStatus(DashboardController::mockDevices($now), $now),
        ]);
    }
}
