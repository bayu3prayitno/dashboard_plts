<?php

namespace App\Models;

use App\Models\Device;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * `status`, `mqtt_status`, dan `internet_status` bukan kolom tabel: semuanya dihitung setiap kali
 * dibaca (lihat accessor di bawah) dari waktu terakhir yang tersimpan, sehingga tidak pernah basi.
 */
#[Fillable(['name', 'last_seen_at', 'internet_last_seen_at'])]
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

    /** Subscriber MQTT sedang terhubung ke broker (dihitung dari heartbeat, bukan dari database). */
    public static function subscriberConnected(): bool
    {
        $at = Cache::get(self::MQTT_HEARTBEAT_KEY);

        return $at !== null && (time() - (int) $at) <= self::MQTT_HEARTBEAT_TIMEOUT;
    }

    /**
     * Status MQTT untuk tampilan, dihitung setiap kali dibaca.
     * Terhubung hanya jika subscriber sedang terhubung ke broker.
     */
    protected function mqttStatus(): Attribute
    {
        return Attribute::get(fn () => self::subscriberConnected() ? 'connected' : 'disconnected');
    }

    /**
     * Status koneksi dihitung dari paket terakhir (node mana pun) yang diterima server dari gateway ini.
     * Terhubung bila paket terakhir masih dalam Offline Timeout.
     */
    protected function internetStatus(): Attribute
    {
        return Attribute::get(fn () => $this->internet_last_seen_at !== null
            && $this->internet_last_seen_at->gte(now()->subMinutes(self::connectionTimeoutMinutes()))
                ? 'connected'
                : 'disconnected');
    }

    /**
     * Status gateway untuk tampilan, dihitung dari waktu data terakhir.
     * Online jika data masuk dalam batas waktu offline (pengaturan), selain itu offline.
     */
    protected function status(): Attribute
    {
        return Attribute::get(function () {
            if ($this->last_seen_at === null) {
                return 'offline';
            }

            $recent = $this->last_seen_at->gte(now()->subMinutes(Device::offlineTimeoutMinutes()));

            return $recent ? 'online' : 'offline';
        });
    }

    protected static function booted(): void
    {
        // Buat kode unik untuk setiap gateway baru (hex 5 digit).
        static::creating(function (Gateway $gateway) {
            do {
                $gateway->gateway_code = sprintf('GW-%05x', random_int(0, 0xFFFFF));
            } while (self::where('gateway_code', $gateway->gateway_code)->exists());
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
