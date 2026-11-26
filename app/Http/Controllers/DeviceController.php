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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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
        $gateways = Gateway::orderBy('name')->get();

        // ID Node berikutnya per gateway, supaya pengguna tidak perlu menebak nomor yang belum dipakai.
        // ID Node hanya unik di dalam satu gateway, jadi gateway lain boleh memakai nomor yang sama.
        $maxPerGateway = Device::query()
            ->selectRaw('gateway_id, MAX(node_id) AS max_node_id')
            ->groupBy('gateway_id')
            ->pluck('max_node_id', 'gateway_id');

        $nextNodeIds = $gateways->mapWithKeys(fn (Gateway $gateway) => [
            $gateway->id => (int) ($maxPerGateway[$gateway->id] ?? 0) + 1,
        ]);

        return view('dashboard.devices.create', [
            'gateways' => $gateways,
            'nextNodeIds' => $nextNodeIds,
            'nextNodeId' => $nextNodeIds[old('gateway_id')] ?? 1,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules($request), $this->messages());
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
        $data = $request->validate($this->rules($request, $device) + [
            'status' => ['required', 'in:active,inactive'],
        ], $this->messages());

        $before = $device->getAttributes();

        $payload = collect($data)->except('status')->all();
        $payload['is_active'] = $data['status'] === 'active';
        $device->update($payload);

        ActivityLog::record($request->user()?->id, 'device.updated', $device, $before, $device->getAttributes());

        return redirect()->route('devices.index')->with('success', 'Perangkat berhasil diperbarui.');
    }

    public function delete(Request $request, Device $device): JsonResponse
    {
        if ($device->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Perangkat aktif tidak dapat dihapus. Nonaktifkan perangkat terlebih dahulu.',
            ], 409);
        }

        $snapshot = $device->getAttributes();

        try {
            DB::transaction(function () use ($device) {
                $device->measurements()->delete();
                $device->alerts()->delete();
                $device->thresholds()->delete();
                $device->delete();
            });
        } catch (QueryException) {
            return response()->json([
                'success' => false,
                'message' => 'Perangkat tidak dapat dihapus karena terjadi kesalahan pada database.',
            ], 409);
        }

        ActivityLog::record($request->user()?->id, 'device.deleted', $device, $snapshot, null);

        return response()->json(['success' => true, 'message' => 'Perangkat berhasil dihapus.']);
    }

    /** API untuk polling: return status device terbaru (online/offline/inactive). */
    public function liveStatus(): JsonResponse
    {
        $offlineMinutes = Device::offlineTimeoutMinutes();
        $devices = Device::query()
            ->orderBy('node_id')
            ->get(['id', 'node_id', 'name', 'is_active', 'last_seen_at'])
            ->map(function (Device $device) use ($offlineMinutes) {
                return [
                    'id' => $device->id,
                    'node_id' => $device->node_id,
                    'name' => $device->name,
                    'status' => $device->status($offlineMinutes),
                    'last_seen' => $device->last_seen_at?->toIso8601String(),
                ];
            })
            ->all();

        return response()->json([
            'devices' => $devices,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * ID Node wajib unik di dalam satu gateway, tetapi boleh sama bila gatewaynya berbeda
     * (topik MQTT vpp/kampus/{kode_gateway}/node{node_id} tetap berbeda).
     * Saat update, perangkat itu sendiri dikecualikan dari pengecekan.
     *
     * @return array<string, array<int, mixed>>
     */
    private function rules(Request $request, ?Device $device = null): array
    {
        $uniquePerGateway = Rule::unique('devices', 'node_id')
            ->where('gateway_id', $request->integer('gateway_id'))
            ->ignore($device?->id);

        return [
            'gateway_id' => ['required', 'integer', 'exists:gateways,id'],
            'node_id' => ['required', 'integer', 'min:1', $uniquePerGateway],
            'name' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'node_id.unique' => 'ID Node :input sudah dipakai perangkat lain di gateway ini. Pilih ID Node lain atau gateway lain.',
        ];
    }
}
