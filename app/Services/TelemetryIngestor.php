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
            // Field sensor boleh tidak ada; yang kosong disimpan sebagai null
            'temperature' => ['nullable', 'numeric', 'between:-999999,999999'],
            'voltage' => ['nullable', 'numeric', 'between:-999999,999999'],
            'current' => ['nullable', 'numeric', 'between:-999999,999999'],
            'power' => ['nullable', 'numeric', 'between:-999999,999999'],
            'irradiance' => ['nullable', 'numeric', 'between:-999999,999999'],
            'rssi' => ['nullable', 'integer'],
            'snr' => ['nullable', 'numeric', 'between:-999999,999999'],
            'packet_loss' => ['nullable', 'numeric', 'between:0,100'],
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

        // Paket dengan node dan nomor yang sama sudah ada: data lama ditimpa dengan yang baru
        $existing = Measurement::where('device_id', $device->id)->where('packet_no', $data['packet_no'])->first();
        $previous = $existing ? null : Measurement::where('device_id', $device->id)
            ->orderByDesc('packet_no')
            ->first();

        return DB::transaction(function () use ($gateway, $device, $data, $existing, $previous) {
            $now = now();

            $fields = [
                'temperature' => $data['temperature'] ?? null,
                'voltage' => $data['voltage'] ?? null,
                'current' => $data['current'] ?? null,
                'power' => $data['power'] ?? null,
                'irradiance' => $data['irradiance'] ?? null,
                'rssi' => $data['rssi'] ?? null,
                'snr' => $data['snr'] ?? null,
                'packet_loss' => $data['packet_loss'] ?? null,
                'recorded_at' => $data['recorded_at'] ?? $now,
            ];

            if ($existing) {
                // Timpa data lama. Ambang batas tidak dievaluasi ulang agar alert yang sudah ada tidak terduplikasi.
                $existing->update($fields);
                Log::info('MQTT: paket duplikat ditimpa', ['gateway' => $gateway->gateway_code, 'node_id' => $data['node_id'], 'packet_no' => $data['packet_no']]);
                $measurement = $existing;
            } else {
                $measurement = Measurement::create([
                    'device_id' => $device->id,
                    'packet_no' => $data['packet_no'],
                    ...$fields,
                ]);
            }

            if (! $existing && $previous) {
                $difference = (int) $data['packet_no'] - (int) $previous->packet_no;

                if ($difference > 1) {
                    Log::warning('MQTT: gap paket terdeteksi', [
                        'gateway' => $gateway->gateway_code,
                        'node_id' => $device->node_id,
                        'packet_sebelumnya' => $previous->packet_no,
                        'packet_saat_ini' => $data['packet_no'],
                        'paket_hilang_tersirat' => $difference - 1,
                    ]);
                } elseif ($difference < 0) {
                    Log::info('MQTT: paket diterima tidak berurutan', [
                        'gateway' => $gateway->gateway_code,
                        'node_id' => $device->node_id,
                        'packet_sebelumnya' => $previous->packet_no,
                        'packet_saat_ini' => $data['packet_no'],
                    ]);
                }
            }

            // Data masuk = node & gateway online. Kembali false lewat status:refresh bila melewati timeout.
            $device->update(['last_seen_at' => $now, 'is_online' => true]);
            $gateway->update(['last_seen_at' => $now, 'is_online' => true]);

            $this->alerts->resolveOffline($device, $now);
            if (! $existing) {
                $this->alerts->evaluateMeasurement($device, $measurement);
            }

            return $measurement;
        });
    }
}
