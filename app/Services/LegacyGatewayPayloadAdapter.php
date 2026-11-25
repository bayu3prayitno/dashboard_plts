<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Gateway;
use App\Models\Measurement;
use Illuminate\Support\Facades\Log;

/**
 * Menerjemahkan payload firmware gateway (topik vpp/kampus/{nama_node}, JSON tanpa node_id
 * dan packet_no) menjadi format yang diterima TelemetryIngestor. Satu-satunya tempat
 * pemetaan format firmware ini.
 *
 * Firmware: {"temp","voltage","current","power","irradiance","rssi","snr"}
 * Hasil  : node_id, packet_no (berurutan dari server), temperature, dan field lain.
 */
class LegacyGatewayPayloadAdapter
{
    /**
     * @param  string  $gatewayCode  kode gateway tujuan (lihat MqttSettings::gatewayCode)
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

    /**
     * Alias nama di config('mqtt.node_aliases') lebih dulu, lalu angka langsung (mis. "2").
     * Null bila tidak dikenali.
     */
    public static function resolveNodeId(string $topicNode): ?int
    {
        $aliases = (array) config('mqtt.node_aliases', []);

        if (array_key_exists($topicNode, $aliases)) {
            return (int) $aliases[$topicNode];
        }

        if (ctype_digit($topicNode) && (int) $topicNode >= 1) {
            return (int) $topicNode;
        }

        return null;
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
