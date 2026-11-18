<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\MqttSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    private const PER_PAGE = 10;

    /** Pengaturan sistem yang disimpan di tabel settings, dengan nilai default dan keterangan. */
    private const SYSTEM_KEYS = [
        'timezone' => ['default' => 'Asia/Jakarta', 'description' => 'Zona waktu untuk tampilan dan filter data'],
        'data_retention_days' => ['default' => '90', 'description' => 'Lama data sensor disimpan (hari)'],
        'offline_timeout_minutes' => ['default' => '15', 'description' => 'Durasi (menit) sebelum perangkat dianggap offline'],
        'beacon_interval_minutes' => ['default' => '5', 'description' => 'Interval beacon Gateway (menit), diatur di firmware'],
    ];

    public const TIMEZONES = ['Asia/Jakarta', 'Asia/Makassar', 'Asia/Jayapura', 'UTC'];

    public function index(): View
    {
        return view('dashboard.settings.index');
    }

    public function users(): View
    {
        return view('dashboard.settings.users', [
            'users' => User::query()->orderBy('name')->get(),
        ]);
    }

    public function systemSettings(): View
    {
        return view('dashboard.settings.system', [
            'settings' => $this->currentSystemSettings(),
            'mqtt' => MqttSettings::forForm(),
            'mqttHasPassword' => Setting::query()->where('key', 'mqtt_password')->where('value', '!=', '')->exists(),
        ]);
    }

    /** Simpan konfigurasi broker MQTT. Password kosong berarti tetap memakai yang tersimpan. */
    public function updateMqttSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mqtt_host' => ['required', 'string', 'max:255'],
            'mqtt_port' => ['required', 'integer', 'between:1,65535'],
            'mqtt_username' => ['nullable', 'string', 'max:255'],
            'mqtt_password' => ['nullable', 'string', 'max:255'],
        ]);

        $before = MqttSettings::forForm();
        $userId = $request->user()?->id;

        $values = [
            'mqtt_host' => trim($data['mqtt_host']),
            'mqtt_port' => (string) $data['mqtt_port'],
            'mqtt_username' => trim((string) ($data['mqtt_username'] ?? '')),
            'mqtt_tls' => $request->boolean('mqtt_tls') ? '1' : '0',
        ];

        foreach ($values as $key => $value) {
            if ($before[$key] !== $value) {
                $this->storeMqttValue($key, $value, $before[$key], $value, $userId);
            }
        }

        // Password: diisi = ganti, kosong = biarkan, centang hapus = kosongkan.
        $newPassword = (string) ($data['mqtt_password'] ?? '');
        if ($request->boolean('mqtt_clear_password')) {
            $this->storeMqttValue('mqtt_password', '', null, null, $userId, '');
        } elseif ($newPassword !== '') {
            $this->storeMqttValue('mqtt_password', MqttSettings::encryptPassword($newPassword), null, null, $userId, '(diubah)');
        }

        return redirect()->route('settings.system')->with('success', 'Konfigurasi MQTT disimpan. Subscriber akan dimulai ulang otomatis.');
    }

    /** Simpan satu nilai MQTT dan catat di audit log. Password tidak pernah dicatat nilainya. */
    private function storeMqttValue(string $key, string $stored, ?string $before, ?string $after, ?int $userId, ?string $maskedValue = null): void
    {
        $setting = Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $stored, 'description' => MqttSettings::KEYS[$key]['description']],
        );

        if ($maskedValue !== null) {
            ActivityLog::record($userId, 'setting.updated', $setting, [$key => '***'], [$key => $maskedValue]);

            return;
        }

        ActivityLog::record($userId, 'setting.updated', $setting, [$key => $before], [$key => $after]);
    }

    /** Simpan pengaturan sistem. Perubahan setiap nilai dicatat di audit log. */
    public function updateSystemSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'timezone' => ['required', Rule::in(self::TIMEZONES)],
            'data_retention_days' => ['required', 'integer', 'min:30', 'max:3650'],
            'offline_timeout_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
        ]);

        $before = $this->currentSystemSettings();
        $userId = $request->user()?->id;

        foreach ($data as $key => $value) {
            $value = (string) ($value ?? '');
            if ($before[$key] === $value) {
                continue;
            }

            $setting = Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'description' => self::SYSTEM_KEYS[$key]['description']],
            );

            ActivityLog::record($userId, 'setting.updated', $setting, [$key => $before[$key]], [$key => $value]);
        }

        return redirect()->route('settings.system')->with('success', 'Pengaturan sistem disimpan.');
    }

    /** Audit log dengan filter pencarian dan pengguna, serta ringkasan jumlah aktivitas. */
    public function auditLog(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $query = ActivityLog::query()->with('user:id,name')->latest('created_at');

        if (! empty($filters['q'])) {
            $term = '%'.$filters['q'].'%';
            $query->where(fn ($q) => $q->where('action', 'like', $term)->orWhere('subject_type', 'like', $term));
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        $now = now();

        return view('dashboard.settings.audit', [
            'logs' => $query->paginate(self::PER_PAGE)->withQueryString(),
            'filters' => ['q' => $filters['q'] ?? '', 'user_id' => $filters['user_id'] ?? null],
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'stats' => [
                'total' => ActivityLog::count(),
                'today' => ActivityLog::where('created_at', '>=', $now->copy()->startOfDay())->count(),
                'week' => ActivityLog::where('created_at', '>=', $now->copy()->startOfWeek())->count(),
                'month' => ActivityLog::where('created_at', '>=', $now->copy()->startOfMonth())->count(),
            ],
        ]);
    }

    public function scopeAssignment(): View
    {
        $admins = [
            [
                'id' => 2,
                'name' => 'Admin Lokasi A',
                'email' => 'admin.a@example.com',
                'assigned_locations' => ['Lokasi A'],
                'assigned_devices' => ['DEVICE_001', 'DEVICE_002'],
            ],
        ];

        $allLocations = ['Lokasi A', 'Lokasi B', 'Lokasi C'];
        $allDevices = [
            ['id' => 1, 'name' => 'Node Sensor 01', 'location' => 'Lokasi A'],
            ['id' => 2, 'name' => 'Node Sensor 02', 'location' => 'Lokasi B'],
            ['id' => 3, 'name' => 'Node Sensor 03', 'location' => 'Lokasi C'],
        ];

        return view('dashboard.settings.scope', [
            'admins' => $admins,
            'allLocations' => $allLocations,
            'allDevices' => $allDevices,
        ]);
    }

    public function updateScope(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Scope assignment updated successfully',
        ]);
    }

    /** Nilai pengaturan sistem saat ini: dari tabel settings, atau default bila belum pernah disimpan. */
    private function currentSystemSettings(): array
    {
        $stored = Setting::query()->whereIn('key', array_keys(self::SYSTEM_KEYS))->pluck('value', 'key');

        return collect(self::SYSTEM_KEYS)
            ->map(fn (array $meta, string $key) => (string) ($stored[$key] ?? $meta['default']))
            ->all();
    }
}
