<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Device;
use App\Models\Gateway;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeviceController extends Controller
{
    public function index(): View
    {
        $devices = Device::with('gateway')->orderBy('node_id')->paginate(10);

        return view('dashboard.devices.index', ['devices' => $devices]);
    }

    public function show(Device $device): View
    {
        return view('dashboard.devices.show', [
            'device' => $device->load('gateway'),
            'latest' => $device->measurements()->latest('recorded_at')->first(),
            'alerts' => $device->alerts()->latest('triggered_at')->limit(5)->get(),
        ]);
    }

    public function create(): View
    {
        return view('dashboard.devices.create', [
            'gateways' => Gateway::orderBy('name')->get(),
            // ID Node berikutnya, supaya pengguna tidak perlu menebak nomor yang belum dipakai
            'nextNodeId' => (int) Device::max('node_id') + 1,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $data['is_active'] = true;

        $device = Device::create($data);
        ActivityLog::record($request->user()?->id, 'device.created', $device, null, $device->getAttributes());

        // Langsung ke halaman detail, tempat konfigurasi firmware ditampilkan
        return redirect()->route('devices.show', $device)
            ->with('success', 'Perangkat berhasil ditambahkan. Salin konfigurasi firmware di bawah ke program Node.');
    }

    public function edit(Device $device): View
    {
        return view('dashboard.devices.edit', [
            'device' => $device,
            'gateways' => Gateway::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Device $device): RedirectResponse
    {
        $data = $request->validate($this->rules() + [
            'status' => ['required', 'in:active,inactive'],
        ]);

        $before = $device->getAttributes();

        $payload = collect($data)->except('status')->all();
        $payload['is_active'] = $data['status'] === 'active';
        $device->update($payload);

        ActivityLog::record($request->user()?->id, 'device.updated', $device, $before, $device->getAttributes());

        return redirect()->route('devices.index')->with('success', 'Perangkat berhasil diperbarui.');
    }

    public function delete(Request $request, Device $device): JsonResponse
    {
        $snapshot = $device->getAttributes();

        try {
            $device->delete();
        } catch (QueryException) {
            // Perangkat yang sudah punya data (measurements, alerts, thresholds) tidak boleh dihapus
            return response()->json([
                'success' => false,
                'message' => 'Perangkat tidak dapat dihapus karena sudah memiliki riwayat data. Nonaktifkan perangkat saja.',
            ], 409);
        }

        ActivityLog::record($request->user()?->id, 'device.deleted', $device, $snapshot, null);

        return response()->json(['success' => true, 'message' => 'Perangkat berhasil dihapus.']);
    }

    /** @return array<string, array<int, string>> */
    private function rules(): array
    {
        return [
            'gateway_id' => ['required', 'integer', 'exists:gateways,id'],
            'node_id' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
        ];
    }
}
