<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Gateway;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GatewayController extends Controller
{
    public function index(): View
    {
        $gateways = Gateway::withCount('devices')->orderBy('name')->get();

        return view('dashboard.gateway.index', ['gateways' => $gateways]);
    }

    public function show(Gateway $gateway): View
    {
        // Setiap Node beserta pengukuran terakhirnya (untuk RSSI, SNR, packet loss)
        $devices = $gateway->devices()->orderBy('node_id')->get()->map(fn ($device) => [
            'device' => $device,
            'latest' => $device->measurements()->latest('recorded_at')->first(),
        ]);

        return view('dashboard.gateway.show', [
            'gateway' => $gateway,
            'devices' => $devices,
        ]);
    }

    /** Form tambah kini berupa modal di halaman daftar; URL lama tetap berfungsi dan langsung membuka modal. */
    public function create(): RedirectResponse
    {
        return redirect()->route('gateway.index', ['create' => 1]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Kode gateway dibuat otomatis oleh model Gateway.
        // Error bag terpisah agar modal di halaman daftar tahu kapan harus dibuka ulang.
        $data = $request->validateWithBag('createGateway', [
            'name' => ['required', 'string', 'max:255'],
        ]);

        // Status koneksi diperbarui oleh sistem (MQTT subscriber), bukan oleh pengguna
        $data += [
            'status' => 'offline',
            'mqtt_status' => 'disconnected',
            'internet_status' => 'disconnected',
        ];

        $gateway = Gateway::create($data);
        ActivityLog::record($request->user()?->id, 'gateway.created', $gateway, null, $gateway->getAttributes());

        return redirect()->route('gateway.show', $gateway)->with('success', 'Gateway berhasil ditambahkan.');
    }

    public function edit(Gateway $gateway): View
    {
        return view('dashboard.gateway.edit', ['gateway' => $gateway]);
    }

    public function update(Request $request, Gateway $gateway): RedirectResponse
    {
        // Kode gateway tidak diubah setelah dibuat, karena terikat ke perangkat keras
        $before = $gateway->getAttributes();

        $gateway->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]));

        ActivityLog::record($request->user()?->id, 'gateway.updated', $gateway, $before, $gateway->getAttributes());

        return redirect()->route('gateway.show', $gateway)->with('success', 'Gateway berhasil diperbarui.');
    }

    public function delete(Request $request, Gateway $gateway): JsonResponse
    {
        $snapshot = $gateway->getAttributes();

        try {
            $gateway->delete();
        } catch (QueryException) {
            // Gateway yang masih punya Node tidak boleh dihapus
            return response()->json([
                'success' => false,
                'message' => 'Gateway tidak dapat dihapus karena masih memiliki perangkat terhubung.',
            ], 409);
        }

        ActivityLog::record($request->user()?->id, 'gateway.deleted', $gateway, $snapshot, null);

        return response()->json(['success' => true, 'message' => 'Gateway berhasil dihapus.']);
    }
}
