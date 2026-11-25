<?php

namespace App\Models;

use App\Models\Device;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable(['name', 'status', 'mqtt_status', 'internet_status', 'last_seen_at', 'internet_last_seen_at'])]
class Gateway extends Model
{
    /** Internet dianggap putus bila tidak ada pesan dari gateway dalam kurun ini (siklus node 5 menit). */
    public const INTERNET_TIMEOUT_MINUTES = 15;

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
     * Status internet dihitung dari pesan terakhir gateway yang diterima server.
     * Terhubung bila pesan terakhir masih dalam INTERNET_TIMEOUT_MINUTES.
     */
    protected function internetStatus(): Attribute
    {
        return Attribute::get(function (?string $value) {
            if ($value !== 'connected' || $this->internet_last_seen_at === null) {
                return 'disconnected';
            }

            return $this->internet_last_seen_at->gte(now()->subMinutes(self::INTERNET_TIMEOUT_MINUTES))
                ? 'connected'
                : 'disconnected';
        });
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
        // Kode gateway dibuat otomatis dengan 128 bit entropi acak kriptografis
        // dan tidak dapat ditebak dari urutan atau ID database.
        static::creating(function (Gateway $gateway) {
            do {
                $gateway->gateway_code = 'GW-'.bin2hex(random_bytes(7));
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
