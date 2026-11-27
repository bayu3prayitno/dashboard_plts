<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Gateway;
use Illuminate\Database\Eloquent\Builder;

/**
 * Menjaga kolom status boolean sama dengan aturan yang dulu dihitung di tampilan:
 * - devices.is_online     : last_seen_at dalam Offline Timeout
 * - gateways.is_online    : last_seen_at (paket data valid) dalam Offline Timeout
 * - gateways.is_connected : internet_last_seen_at (pesan apa pun) dalam Offline Timeout
 * - gateways.is_mqtt_connected : heartbeat subscriber tidak lebih tua dari 30 detik
 */
class ConnectionStatus
{
    /** Hitung ulang semua status. Dijalankan tiap 5 detik (status:refresh). */
    public function refresh(): void
    {
        $cutoff = now()->subMinutes(Device::offlineTimeoutMinutes());

        $this->sync(Device::query(), 'is_online', 'last_seen_at', $cutoff);
        $this->sync(Gateway::query(), 'is_online', 'last_seen_at', $cutoff);
        $this->sync(Gateway::query(), 'is_connected', 'internet_last_seen_at', $cutoff);

        $this->setMqttConnected(Gateway::subscriberConnected());
    }

    /** Kolom status = (waktu terakhir >= batas). Dua arah, hanya baris yang berubah yang ditulis. */
    private function sync(Builder $query, string $flag, string $seenColumn, \DateTimeInterface $cutoff): void
    {
        (clone $query)->where($flag, true)
            ->where(fn ($q) => $q->whereNull($seenColumn)->orWhere($seenColumn, '<', $cutoff))
            ->update([$flag => false]);

        (clone $query)->where($flag, false)->where($seenColumn, '>=', $cutoff)
            ->update([$flag => true]);
    }

    /** Status koneksi subscriber ke broker berlaku untuk semua gateway. Hanya menulis baris yang berubah. */
    public function setMqttConnected(bool $connected): void
    {
        Gateway::query()->where('is_mqtt_connected', ! $connected)->update(['is_mqtt_connected' => $connected]);
    }
}
