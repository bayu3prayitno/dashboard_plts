<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Gateway;
use App\Models\Measurement;
use App\Services\AlertEvaluator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Menyimpan satu paket data dari Gateway (diterima lewat MQTT) ke tabel measurements.
 * Data yang tidak valid atau tidak dikenali ditolak dan dicatat ke log.
 */
class TelemetryIngestor
{
    public function __construct(private AlertEvaluator $alerts) {}

    public function ingest(string $gatewayCode, array $payload): ?Measurement
    {
        $validator = Validator::make($payload, [
            'node_id' => ['required', 'integer', 'min:1'],
            'packet_no' => ['required', 'integer', 'min:0'],
            'recorded_at' => ['nullable', 'date'],
            'temperature' => ['required', 'numeric', 'between:-999999,999999'],
            'voltage' => ['required', 'numeric', 'between:-999999,999999'],
            'current' => ['required', 'numeric', 'between:-999999,999999'],
            'power' => ['required', 'numeric', 'between:-999999,999999'],
            'irradiance' => ['required', 'numeric', 'between:-999999,999999'],
            'rssi' => ['required', 'integer'],
            'snr' => ['required', 'numeric', 'between:-999999,999999'],
            'packet_loss' => ['required', 'numeric', 'between:0,100'],
        ]);

        if ($validator->fails()) {
            Log::warning('MQTT: payload tidak valid', ['gateway' => $gatewayCode, 'errors' => $validator->errors()->all()]);

            return null;
        }

        $data = $validator->validated();

        $gateway = Gateway::where('gateway_code', $gatewayCode)->first();
        if (! $gateway) {
            Log::warning('MQTT: gateway tidak dikenal', ['gateway' => $gatewayCode]);

            return null;
        }

        // Node harus terdaftar di gateway ini
        $device = Device::where('gateway_id', $gateway->id)->where('node_id', $data['node_id'])->first();
        if (! $device) {
            Log::warning('MQTT: node tidak terdaftar di gateway', ['gateway' => $gatewayCode, 'node_id' => $data['node_id']]);

            return null;
        }

        if (! $device->is_active) {
            return null; // perangkat nonaktif: data diabaikan
        }

        // Paket yang sama tidak disimpan dua kali
        $duplicate = Measurement::where('device_id', $device->id)->where('packet_no', $data['packet_no'])->exists();
        if ($duplicate) {
            Log::info('MQTT: paket duplikat diabaikan', ['gateway' => $gatewayCode, 'node_id' => $data['node_id'], 'packet_no' => $data['packet_no']]);

            return null;
        }

        return DB::transaction(function () use ($gateway, $device, $data) {
            $now = now();

            $measurement = Measurement::create([
                'device_id' => $device->id,
                'packet_no' => $data['packet_no'],
                'temperature' => $data['temperature'],
                'voltage' => $data['voltage'],
                'current' => $data['current'],
                'power' => $data['power'],
                'irradiance' => $data['irradiance'],
                'rssi' => $data['rssi'],
                'snr' => $data['snr'],
                'packet_loss' => $data['packet_loss'],
                'recorded_at' => $data['recorded_at'] ?? $now,
            ]);

            // Status online/offline dan MQTT dihitung saat dibaca (lihat Gateway), hanya waktu terakhir yang disimpan
            $device->update(['last_seen_at' => $now]);
            $gateway->update(['last_seen_at' => $now]);

            $this->alerts->resolveOffline($device, $now);
            $this->alerts->evaluateMeasurement($device, $measurement);

            return $measurement;
        });
    }
}
