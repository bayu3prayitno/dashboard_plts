<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Alert;
use App\Models\Device;
use App\Models\Setting;
use App\Models\Threshold;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AlertController extends Controller
{
    private const PER_PAGE = 15;

    private const STATUSES = [Alert::STATUS_NEW, Alert::STATUS_ACKNOWLEDGED, Alert::STATUS_RESOLVED];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'type' => ['nullable', Rule::in(['offline', 'threshold'])],
        ]);

        $query = Alert::query()->with('device:id,name,node_id')->latest('triggered_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (($filters['type'] ?? null) === 'offline') {
            $query->where('alert_type', Alert::TYPE_OFFLINE);
        } elseif (($filters['type'] ?? null) === 'threshold') {
            $query->whereIn('alert_type', [Alert::TYPE_THRESHOLD_HIGH, Alert::TYPE_THRESHOLD_LOW]);
        }

        return view('dashboard.alerts.index', [
            'alerts' => $query->paginate(self::PER_PAGE)->withQueryString(),
            'filters' => ['status' => $filters['status'] ?? '', 'type' => $filters['type'] ?? ''],
            'counts' => [
                Alert::STATUS_NEW => Alert::where('status', Alert::STATUS_NEW)->count(),
                Alert::STATUS_ACKNOWLEDGED => Alert::where('status', Alert::STATUS_ACKNOWLEDGED)->count(),
                Alert::STATUS_RESOLVED => Alert::where('status', Alert::STATUS_RESOLVED)->count(),
            ],
        ]);
    }

    /** Ubah status penanganan alert (Baru, Ditangani, Selesai). */
    public function updateStatus(Request $request, Alert $alert): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(self::STATUSES)],
            'resolution_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $userId = $request->user()?->id;

        match ($data['status']) {
            Alert::STATUS_NEW => $alert->fill(['handled_by' => null, 'resolved_at' => null, 'resolution_note' => null]),
            Alert::STATUS_ACKNOWLEDGED => $alert->fill(['handled_by' => $userId, 'resolved_at' => null]),
            Alert::STATUS_RESOLVED => $alert->fill([
                'handled_by' => $alert->handled_by ?? $userId,
                'resolved_at' => now(),
                'resolution_note' => $data['resolution_note'] ?? null,
            ]),
        };

        $alert->status = $data['status'];
        $alert->save();

        return response()->json([
            'success' => true,
            'message' => 'Status alert diperbarui.',
            'status' => $alert->status,
        ]);
    }

    public function configuration(): View
    {
        return view('dashboard.alerts.configuration', [
            'offlineTimeout' => Device::offlineTimeoutMinutes(),
            'thresholds' => Threshold::query()->whereNull('device_id')->get()->keyBy('sensor_type'),
            'sensors' => Threshold::SENSOR_INFO,
        ]);
    }

    /** Simpan timeout offline dan ambang batas global. Setiap perubahan dicatat di audit log (PRD 4.5). */
    public function saveConfiguration(Request $request): RedirectResponse
    {
        $rules = ['offline_timeout' => ['required', 'integer', 'min:5', 'max:1440']];

        foreach (array_keys(Threshold::SENSOR_INFO) as $sensor) {
            $rules["thresholds.$sensor.min"] = ['required', 'numeric', 'between:-999999,999999'];
            $rules["thresholds.$sensor.max"] = ['required', 'numeric', 'between:-999999,999999', "gte:thresholds.$sensor.min"];
        }

        $data = $request->validate($rules, [
            'thresholds.*.max.gte' => 'Nilai maksimal harus lebih besar atau sama dengan nilai minimal.',
        ]);

        $userId = $request->user()?->id;

        $oldTimeout = Device::offlineTimeoutMinutes();
        if ($oldTimeout !== (int) $data['offline_timeout']) {
            $setting = Setting::updateOrCreate(
                ['key' => 'offline_timeout_minutes'],
                ['value' => (string) $data['offline_timeout'], 'description' => 'Durasi (menit) sebelum perangkat dianggap offline'],
            );
            ActivityLog::record($userId, 'setting.updated', $setting, ['offline_timeout_minutes' => $oldTimeout], ['offline_timeout_minutes' => (int) $data['offline_timeout']]);
        }

        foreach (array_keys(Threshold::SENSOR_INFO) as $sensor) {
            $threshold = Threshold::query()->whereNull('device_id')->where('sensor_type', $sensor)->first()
                ?? Threshold::create(['device_id' => null, 'sensor_type' => $sensor, 'min_value' => 0, 'max_value' => 0, 'is_active' => true]);

            $before = $threshold->getAttributes();
            $threshold->update([
                'min_value' => $data['thresholds'][$sensor]['min'],
                'max_value' => $data['thresholds'][$sensor]['max'],
            ]);

            if ($threshold->wasChanged()) {
                ActivityLog::record($userId, 'threshold.updated', $threshold, $before, $threshold->getAttributes());
            }
        }

        return redirect()->route('alerts.configuration')->with('success', 'Konfigurasi alert disimpan.');
    }

    /**
     * Notifikasi real-time lewat polling super ringan.
     *
     * ?v=<versi>  : versi yang dimiliki browser. Bila sama dengan versi server → 204 tanpa query DB.
     * ?after=<id> : id alert terakhir yang sudah di-toast. Tanpa ini → hanya titik awal (tanpa toast).
     *
     * Bila berubah, mengembalikan versi baru, jumlah badge, isi panel notifikasi, dan alert baru untuk toast.
     */
    public function liveStream(Request $request): \Illuminate\Http\Response|JsonResponse
    {
        $version = Alert::version();
        if ($request->query('v') === $version) {
            return response()->noContent(); // tidak ada perubahan: 0 query
        }

        $counts = Alert::query()->open()
            ->selectRaw('COUNT(*) AS open_count, COALESCE(SUM(status = ?), 0) AS fresh_count', [Alert::STATUS_NEW])
            ->first();

        $panel = (int) $counts->fresh_count === 0 ? collect() : Alert::query()
            ->where('status', Alert::STATUS_NEW)
            ->with('device:id,name')
            ->latest('triggered_at')
            ->limit(5)
            ->get();

        $after = $request->filled('after') ? (int) $request->query('after') : null;
        $new = $after === null ? collect() : Alert::query()
            ->where('id', '>', $after)
            ->with('device:id,name')
            ->orderBy('id')
            ->limit(10)
            ->get();

        return response()->json([
            'v' => $version,
            'last_id' => (int) ($new->last()?->id ?? $after ?? Alert::query()->max('id')),
            'open' => (int) $counts->open_count,
            'fresh' => (int) $counts->fresh_count,
            'panel' => $panel->map(fn (Alert $a) => [
                'category' => $a->typeLabel(),
                'device' => $a->device?->name ?? '-',
                'text' => $a->message(),
                'time' => $a->triggered_at?->toIso8601String(),
            ])->values(),
            'alerts' => $new->map(fn (Alert $a) => [
                'id' => $a->id,
                'device' => $a->device?->name ?? '-',
                'message' => $a->message(),
            ])->values(),
        ]);
    }
}
