<?php

namespace App\Models;

use App\Support\SettingsCache;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['gateway_id', 'node_id', 'name', 'location', 'is_active', 'last_seen_at'])]
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
     * Status untuk tampilan: nonaktif, online, atau offline (dihitung dari last_seen_at).
     * Kirim $offlineMinutes bila sudah dibaca, agar tidak query settings berulang.
     */
    public function status(?int $offlineMinutes = null): string
    {
        if (! $this->is_active) {
            return self::STATUS_INACTIVE;
        }

        $offlineMinutes ??= self::offlineTimeoutMinutes();

        if ($this->last_seen_at === null || $this->last_seen_at->lt(now()->subMinutes($offlineMinutes))) {
            return self::STATUS_OFFLINE;
        }

        return self::STATUS_ONLINE;
    }

    public function gateway(): BelongsTo
    {
        return $this->belongsTo(Gateway::class);
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
