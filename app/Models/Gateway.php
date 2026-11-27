<?php

namespace App\Models;

use App\Models\Device;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Status disimpan sebagai boolean:
 * - is_online         : paket data valid dalam Offline Timeout (dari last_seen_at)
 * - is_connected      : pesan apa pun dari gateway dalam Offline Timeout (dari internet_last_seen_at)
 * - is_mqtt_connected : subscriber server terhubung ke broker (heartbeat ≤ 30 detik)
 * Diperbarui saat data/pesan masuk dan tiap 5 detik oleh `status:refresh` (App\Services\ConnectionStatus).
 * `status`, `internet_status`, dan `mqtt_status` hanyalah label teks dari kolom itu untuk tampilan/API.
 */
#[Fillable(['name', 'is_online', 'is_connected', 'is_mqtt_connected', 'last_seen_at', 'internet_last_seen_at'])]
class Gateway extends Model
{
    /**
     * Gateway dianggap terputus bila tidak ada paket sama sekali (dari node mana pun) melebihi
     * Offline Timeout (pengaturan offline_timeout_minutes). Satu paket dari node mana saja cukup
     * untuk menjaganya tetap terhubung.
     */
    public static function connectionTimeoutMinutes(): int
    {
        return Device::offlineTimeoutMinutes();
    }

    /** Kunci cache tempat subscriber (mqtt:subscribe) menyimpan waktu heartbeat terakhir. */
    public const MQTT_HEARTBEAT_KEY = 'mqtt:subscriber:heartbeat';

    /** Subscriber dianggap terputus bila heartbeat lebih tua dari detik ini. */
    public const MQTT_HEARTBEAT_TIMEOUT = 30;

    /** Heartbeat subscriber masih baru. Dipakai status:refresh untuk mengisi is_mqtt_connected. */
    public static function subscriberConnected(): bool
    {
        $at = Cache::get(self::MQTT_HEARTBEAT_KEY);

        return $at !== null && (time() - (int) $at) <= self::MQTT_HEARTBEAT_TIMEOUT;
    }

    /** Label MQTT dari kolom is_mqtt_connected. */
    protected function mqttStatus(): Attribute
    {
        return Attribute::get(fn () => $this->is_mqtt_connected ? 'connected' : 'disconnected');
    }

    /** Label koneksi gateway dari kolom is_connected. */
    protected function internetStatus(): Attribute
    {
        return Attribute::get(fn () => $this->is_connected ? 'connected' : 'disconnected');
    }

    /** Label online/offline gateway dari kolom is_online. */
    protected function status(): Attribute
    {
        return Attribute::get(fn () => $this->is_online ? 'online' : 'offline');
    }

    protected static function booted(): void
    {
        // Buat kode unik untuk setiap gateway baru (hex 5 digit).
        static::creating(function (Gateway $gateway) {
            do {
                $gateway->gateway_code = sprintf('GW-%05x', random_int(0, 0xFFFFF));
            } while (self::where('gateway_code', $gateway->gateway_code)->exists());

            // Status MQTT milik subscriber (berlaku untuk semua gateway): gateway baru langsung ikut,
            // tanpa menunggu heartbeat berikutnya.
            $gateway->is_mqtt_connected ??= self::subscriberConnected();
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_online' => 'boolean',
            'is_connected' => 'boolean',
            'is_mqtt_connected' => 'boolean',
            'last_seen_at' => 'datetime',
            'internet_last_seen_at' => 'datetime',
        ];
    }

    /** Perangkat (Node) yang dikelola Gateway ini. */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }
}
