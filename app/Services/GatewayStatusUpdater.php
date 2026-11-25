<?php

namespace App\Services;

use App\Models\Gateway;
use Illuminate\Support\Facades\Log;

/**
 * Status internet gateway. Firmware tidak mengirim status internet, jadi status dihitung dari
 * kedatangan pesan: bila pesan dari gateway sampai ke server lewat broker, koneksi internet
 * gateway berfungsi pada saat itu. Lihat Gateway::internetStatus untuk batas waktunya.
 */
class GatewayStatusUpdater
{
    /** Dipanggil setiap pesan dari gateway diterima subscriber. @return bool true bila gateway dikenal */
    public function markInternetConnected(string $gatewayCode): bool
    {
        $updated = Gateway::where('gateway_code', $gatewayCode)->update([
            'internet_status' => 'connected',
            'internet_last_seen_at' => now(),
        ]);

        if ($updated === 0) {
            Log::warning('MQTT: pesan dari gateway tidak dikenal', ['gateway' => $gatewayCode]);

            return false;
        }

        return true;
    }
}
