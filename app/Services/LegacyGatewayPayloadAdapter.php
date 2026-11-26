<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Gateway;
use App\Models\Measurement;
use Illuminate\Support\Facades\Log;

/**
 * Menerjemahkan payload firmware gateway (topik vpp/kampus/{kode_gateway}/node{node_id}, JSON tanpa node_id
 * dan packet_no) menjadi format yang diterima TelemetryIngestor. Satu-satunya tempat
 * pemetaan format firmware ini.
 *
 * Firmware: {"temp","voltage","current","power","irradiance","rssi","snr"}
 * Hasil  : node_id, packet_no (berurutan dari server), temperature, dan field lain.
 */
class LegacyGatewayPayloadAdapter
{
    /**
     * @param  string  $gatewayCode  kode gateway dari topik, mis. "GW-3fa9c"
     * @param  string  $topicNode  segmen terakhir topik, mis. "node1"
     * @param  array<string, mixed>  $payload  JSON dari firmware
     * @return array<string, mixed>|null data siap diingest, atau null bila node tidak bisa dipetakan
     */
    public function translate(string $gatewayCode, string $topicNode, array $payload): ?array
    {
        $nodeId = self::resolveNodeId($topicNode);
        if ($nodeId === null) {
            Log::warning('MQTT: nama node pada topik tidak dikenal', ['topic_node' => $topicNode, 'gateway' => $gatewayCode]);

            return null;
        }

        $gateway = Gateway::where('gateway_code', $gatewayCode)->first();
        $device = $gateway
            ? Device::where('gateway_id', $gateway->id)->where('node_id', $nodeId)->first()
            : null;

        if (! $device) {
            Log::warning('MQTT: node tidak terdaftar di gateway', ['gateway' => $gatewayCode, 'node_id' => $nodeId]);

            return null;
        }

        // Firmware tidak mengirim nomor paket: beri nomor berurutan dari data terakhir node ini
        $packetNo = (int) Measurement::where('device_id', $device->id)->max('packet_no') + 1;

        return [
            'node_id' => $nodeId,
            'packet_no' => $packetNo,
            'temperature' => self::number($payload['temp'] ?? null),
            'voltage' => self::number($payload['voltage'] ?? null),
            'current' => self::number($payload['current'] ?? null),
            'power' => self::number($payload['power'] ?? null),
            'irradiance' => self::number($payload['irradiance'] ?? null),
            'rssi' => self::integer($payload['rssi'] ?? null),
            'snr' => self::number($payload['snr'] ?? null),
            // Firmware hanya mengirim jumlah hilang kumulatif, bukan persen: tidak dipakai
            'packet_loss' => null,
            // Waktu terima server dipakai (firmware tidak mengirim waktu)
            'recorded_at' => null,
        ];
    }

    /** Nama node berformat node{node_id}, mis. "node1" => 1. Null untuk format lain (mis. "2", "node01"). */
    public static function resolveNodeId(string $topicNode): ?int
    {
        return preg_match('/^node([1-9][0-9]*)$/', $topicNode, $match) === 1 ? (int) $match[1] : null;
    }

    /** Angka yang valid dan terbatas; NaN/Infinity atau teks lain dianggap kosong. */
    private static function number(mixed $value): ?float
    {
        return is_numeric($value) && is_finite((float) $value) ? (float) $value : null;
    }

    private static function integer(mixed $value): ?int
    {
        return is_numeric($value) && is_finite((float) $value) ? (int) round((float) $value) : null;
    }
}
