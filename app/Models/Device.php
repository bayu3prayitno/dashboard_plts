<?php

namespace App\Models;

use App\Support\SettingsCache;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['gateway_id', 'node_id', 'name', 'location', 'is_active', 'is_online', 'last_seen_at'])]
class Device extends Model
{
    public const STATUS_ONLINE = 'online';

    public const STATUS_OFFLINE = 'offline';

    public const STATUS_INACTIVE = 'nonaktif';

    /** Default timeout offline (PRD 5.1: minimal 10–15 menit). */
    public const DEFAULT_OFFLINE_TIMEOUT_MINUTES = 15;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_online' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    /** Timeout offline dalam menit, dari tabel settings (key: offline_timeout_minutes). */
    public static function offlineTimeoutMinutes(): int
    {
        $value = SettingsCache::get('offline_timeout_minutes', (string) self::DEFAULT_OFFLINE_TIMEOUT_MINUTES);

        return is_numeric($value) ? (int) $value : self::DEFAULT_OFFLINE_TIMEOUT_MINUTES;
    }

    /**
     * Status untuk tampilan dari kolom boolean: nonaktif (is_active), lalu online/offline (is_online).
     * is_online diset true saat data masuk dan false oleh `status:refresh` bila melewati Offline Timeout.
     */
    public function status(): string
    {
        if (! $this->is_active) {
            return self::STATUS_INACTIVE;
        }

        return $this->is_online ? self::STATUS_ONLINE : self::STATUS_OFFLINE;
    }

    public function gateway(): BelongsTo
    {
        return $this->belongsTo(Gateway::class);
    }

    /** Topik MQTT perangkat ini: vpp/{kode_gateway}/node{node_id}, mis. vpp/GW-3fa9c/node1. */
    public function mqttTopic(): string
    {
        return 'vpp/'.$this->gateway->gateway_code.'/node'.$this->node_id;
    }

    public function measurements(): HasMany
    {
        return $this->hasMany(Measurement::class);
    }

    /** Ambang batas khusus perangkat ini (ambang standar sistem tidak termasuk di sini). */
    public function thresholds(): HasMany
    {
        return $this->hasMany(Threshold::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }
}
