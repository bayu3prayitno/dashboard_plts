<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GatewayResource;
use App\Models\ActivityLog;
use App\Models\Gateway;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * API JSON untuk gateway. Data masuk dari Gateway tetap lewat MQTT;
 * API ini hanya untuk membaca dan mengelola data gateway (misalnya oleh dashboard).
 */
class GatewayApiController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return GatewayResource::collection(
            Gateway::withCount('devices')->orderBy('name')->get(),
        );
    }

    public function show(Gateway $gateway): GatewayResource
    {
        return new GatewayResource($gateway->loadCount('devices'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
        ]);

        // Status awal dan kode gateway dibuat oleh sistem
        $data += [
            'status' => 'offline',
            'mqtt_status' => 'disconnected',
            'internet_status' => 'disconnected',
        ];

        $gateway = Gateway::create($data);
        ActivityLog::record($request->user()?->id, 'gateway.created', $gateway, null, $gateway->getAttributes());

        return (new GatewayResource($gateway))->response()->setStatusCode(201);
    }

    public function update(Request $request, Gateway $gateway): GatewayResource
    {
        $before = $gateway->getAttributes();

        $gateway->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
        ]));

        ActivityLog::record($request->user()?->id, 'gateway.updated', $gateway, $before, $gateway->getAttributes());

        return new GatewayResource($gateway);
    }

    public function destroy(Request $request, Gateway $gateway): JsonResponse
    {
        $snapshot = $gateway->getAttributes();

        try {
            $gateway->delete();
        } catch (QueryException) {
            return response()->json([
                'message' => 'Gateway tidak dapat dihapus karena masih memiliki perangkat terhubung.',
            ], 409);
        }

        ActivityLog::record($request->user()?->id, 'gateway.deleted', $gateway, $snapshot, null);

        return response()->json(['message' => 'Gateway berhasil dihapus.']);
    }
}
