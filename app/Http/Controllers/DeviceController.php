<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Device;
use Carbon\CarbonImmutable;
use App\Models\Gateway;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeviceController extends Controller
{
    public function index(Request $request): View
    {
        $query = Device::with('gateway');

        // Filter berdasarkan search query (nama, lokasi, atau node_id)
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('node_id', '=', is_numeric($search) ? (int) $search : -1);
            });
        }

        // Filter berdasarkan status (online/offline/inactive)
        if ($status = $request->input('status')) {
            if ($status === 'online') {
                // Online: is_active=true dan last_seen_at masih dalam timeout (~5 menit)
                $query->where('is_active', true)
                      ->where('last_seen_at', '>=', now()->subMinutes(5));
            } elseif ($status === 'offline') {
                // Offline: is_active=true tapi last_seen_at sudah lama atau null
                $query->where('is_active', true)
                      ->where(function ($q) {
                          $q->whereNull('last_seen_at')
                            ->orWhere('last_seen_at', '<', now()->subMinutes(5));
                      });
            } elseif ($status === 'inactive') {
                // Inactive: is_active=false
                $query->where('is_active', false);
            }
        }

        $devices = $query->orderBy('node_id')->paginate(10)->appends($request->query());

        return view('dashboard.devices.index', ['devices' => $devices]);
    }

    public function show(Device $device): View
    {
        return view('dashboard.devices.show', [
            'device' => $device->load('gateway'),
            'latest' => $device->measurements()->latest('recorded_at')->first(),
            'alerts' => $device->alerts()->latest('triggered_at')->limit(5)->get(),
            'trend24' => $this->trend24h($device),
        ]);
    }

    /**
     * Tren 24 jam perangkat: 48 bucket @30 menit (rata-rata), sama seperti grafik di dashboard.
     *
     * @return array{labels: array<int, string>, temperature: array<int, ?float>, voltage: array<int, ?float>, current: array<int, ?float>}
     */
    private function trend24h(Device $device): array
    {
        $now = CarbonImmutable::now(DashboardController::tz());
        $currentBucket = $now->setMinute($now->minute >= 30 ? 30 : 0)->startOfMinute();
        $start = $currentBucket->subMinutes(30 * 47);

        $rows = $device->measurements()
            ->where('recorded_at', '>=', $start->utc()->format('Y-m-d H:i:s'))
            ->get(['recorded_at', 'temperature', 'voltage', 'current']);

        $groups = [];
        foreach ($rows as $row) {
            $t = $row->recorded_at->timezone(DashboardController::tz());
            $groups[$t->format('Y-m-d H').'|'.($t->minute >= 30 ? 1 : 0)][] = $row;
        }

        $out = ['labels' => [], 'temperature' => [], 'voltage' => [], 'current' => []];
        for ($i = 0; $i < 48; $i++) {
            $t = $start->addMinutes(30 * $i);
            $group = collect($groups[$t->format('Y-m-d H').'|'.($t->minute >= 30 ? 1 : 0)] ?? []);
            $out['labels'][] = $t->format('H:i');

            foreach (['temperature', 'voltage', 'current'] as $key) {
                $values = $group->pluck($key)->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v);
                $out[$key][] = $values->isEmpty() ? null : round($values->avg(), 2);
            }
        }

        return $out;
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
