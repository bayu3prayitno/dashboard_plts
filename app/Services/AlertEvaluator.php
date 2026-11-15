<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Device;
use App\Models\Measurement;
use App\Models\Threshold;
use Carbon\CarbonInterface;

/**
 * Membuat dan menutup alert (PRD 4.4):
 * - Threshold breach: dipicu saat nilai sensor melewati ambang per perangkat, atau ambang global bila tidak ada.
 * - Device offline: dipicu saat perangkat tidak mengirim data melewati batas waktu offline.
 */
class AlertEvaluator
{
    /** Periksa satu pengukuran terhadap ambang batas dan buat alert bila melewati batas. */
    public function evaluateMeasurement(Device $device, Measurement $measurement): void
    {
        foreach (array_keys(Threshold::SENSOR_INFO) as $sensor) {
            $threshold = $this->thresholdFor($device, $sensor);
            if ($threshold === null) {
                continue;
            }

            $value = (float) $measurement->{$sensor};
            [$type, $limit] = match (true) {
                $value > (float) $threshold->max_value => [Alert::TYPE_THRESHOLD_HIGH, (float) $threshold->max_value],
                $value < (float) $threshold->min_value => [Alert::TYPE_THRESHOLD_LOW, (float) $threshold->min_value],
                default => [null, null],
            };

            if ($type === null) {
                continue;
            }

            // Satu alert terbuka per perangkat, sensor, dan jenis pelanggaran cukup; jangan dobel
            $alreadyOpen = Alert::query()->open()
                ->where('device_id', $device->id)
                ->where('sensor_type', $sensor)
                ->where('alert_type', $type)
                ->exists();

            if ($alreadyOpen) {
                continue;
            }

            Alert::create([
                'device_id' => $device->id,
                'alert_type' => $type,
                'sensor_type' => $sensor,
                'recorded_value' => $value,
                'threshold_value' => $limit,
                'status' => Alert::STATUS_NEW,
                'triggered_at' => $measurement->recorded_at,
            ]);
        }
    }

    /** Data masuk lagi: tutup alert offline yang masih terbuka untuk perangkat ini. */
    public function resolveOffline(Device $device, CarbonInterface $at): void
    {
        Alert::query()->open()
            ->where('device_id', $device->id)
            ->where('alert_type', Alert::TYPE_OFFLINE)
            ->update([
                'status' => Alert::STATUS_RESOLVED,
                'resolved_at' => $at,
                'resolution_note' => 'Perangkat kembali mengirim data.',
                'updated_at' => $at,
            ]);
    }

    /**
     * Buat alert offline untuk perangkat aktif yang terakhir mengirim data melewati timeout.
     * Perangkat yang belum pernah mengirim data (last_seen_at kosong) tidak diberi alert.
     *
     * @return int jumlah alert baru
     */
    public function checkOffline(CarbonInterface $now): int
    {
        $cutoff = $now->copy()->subMinutes(Device::offlineTimeoutMinutes());

        $devices = Device::query()
            ->where('is_active', true)
            ->whereNotNull('last_seen_at')
            ->where('last_seen_at', '<', $cutoff)
            ->whereDoesntHave('alerts', fn ($q) => $q->open()->where('alert_type', Alert::TYPE_OFFLINE))
            ->get();

        foreach ($devices as $device) {
            Alert::create([
                'device_id' => $device->id,
                'alert_type' => Alert::TYPE_OFFLINE,
                'status' => Alert::STATUS_NEW,
                'triggered_at' => $now,
            ]);
        }

        return $devices->count();
    }

    /** Ambang per perangkat (yang aktif) lebih diutamakan; jika tidak ada, pakai ambang global. */
    private function thresholdFor(Device $device, string $sensor): ?Threshold
    {
        return Threshold::query()
            ->where('is_active', true)
            ->where('sensor_type', $sensor)
            ->where(fn ($q) => $q->where('device_id', $device->id)->orWhereNull('device_id'))
            ->orderByRaw('device_id IS NULL')
            ->first();
    }
}
