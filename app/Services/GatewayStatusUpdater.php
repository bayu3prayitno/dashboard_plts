<?php

namespace App\Services;

use App\Models\Gateway;
use Illuminate\Support\Facades\Log;

/**
 * Memperbarui status internet gateway dari pesan status yang diterbitkan gateway.
 * Topik: omahiot/gateway/{kode_gateway}/status, isi: {"internet": true|false}.
 */
class GatewayStatusUpdater
{
    /** @return bool true bila status tersimpan */
    public function update(string $gatewayCode, array $payload): bool
    {
        if (! array_key_exists('internet', $payload)) {
            Log::warning('MQTT: status gateway tidak memuat field internet', ['gateway' => $gatewayCode]);

            return false;
        }

        $internet = filter_var($payload['internet'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($internet === null) {
            Log::warning('MQTT: nilai internet pada status gateway tidak valid', ['gateway' => $gatewayCode]);

            return false;
        }

        $gateway = Gateway::where('gateway_code', $gatewayCode)->first();
        if (! $gateway) {
            Log::warning('MQTT: status dari gateway tidak dikenal', ['gateway' => $gatewayCode]);

            return false;
        }

        $gateway->update([
            'internet_status' => $internet ? 'connected' : 'disconnected',
            'internet_last_seen_at' => now(),
        ]);

        return true;
    }
}
