<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Throwable;

#[Fillable(['device_id', 'handled_by', 'alert_type', 'sensor_type', 'recorded_value', 'threshold_value', 'status', 'resolution_note', 'triggered_at', 'resolved_at'])]
class Alert extends Model
{
    public const TYPE_OFFLINE = 'offline';

    public const TYPE_THRESHOLD_HIGH = 'threshold_high';

    public const TYPE_THRESHOLD_LOW = 'threshold_low';

    public const STATUS_NEW = 'new';

    public const STATUS_ACKNOWLEDGED = 'acknowledged';

    public const STATUS_RESOLVED = 'resolved';

    /** Kunci cache penanda versi data alert (berubah setiap ada alert dibuat/diubah/dihapus). */
    public const VERSION_KEY = 'alerts:version';

    protected static function booted(): void
    {
        // Notifikasi real-time: browser cukup membandingkan versi ini (tanpa query DB) untuk tahu ada perubahan
        static::saved(fn () => self::bumpVersion());
        static::deleted(fn () => self::bumpVersion());
    }

    /**
     * Tandai data alert berubah. Panggil manual setelah mass update (query()->update()),
     * karena mass update tidak memicu event model.
     */
    public static function bumpVersion(): void
    {
        try {
            Cache::forever(self::VERSION_KEY, sprintf('%d-%04d', (int) (microtime(true) * 1000), random_int(0, 9999)));
        } catch (Throwable) {
            // Cache tidak tersedia: notifikasi tetap tersusul pada perubahan berikutnya
        }
    }

    /** Versi data alert saat ini (dibaca dari cache, tanpa query database). */
    public static function version(): string
    {
        try {
            $version = Cache::get(self::VERSION_KEY);
            if ($version === null) {
                self::bumpVersion();
                $version = Cache::get(self::VERSION_KEY, '0');
            }

            return (string) $version;
        } catch (Throwable) {
            return '0';
        }
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recorded_value' => 'decimal:2',
            'threshold_value' => 'decimal:2',
            'triggered_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    /** Alert yang belum diselesaikan (baru atau sedang ditangani). */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_RESOLVED);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /** Pengguna yang menangani alert (NULL jika belum ditangani). */
    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function typeLabel(): string
    {
        return $this->alert_type === self::TYPE_OFFLINE ? 'Device Offline' : 'Threshold Breach';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_NEW => 'Baru',
            self::STATUS_ACKNOWLEDGED => 'Ditangani',
            default => 'Selesai',
        };
    }

    /** Pesan singkat untuk ditampilkan, disusun dari data alert. */
    public function message(): string
    {
        if ($this->alert_type === self::TYPE_OFFLINE) {
            return 'Perangkat tidak mengirim data melewati batas waktu offline';
        }

        $info = Threshold::SENSOR_INFO[$this->sensor_type] ?? ['label' => 'Nilai', 'unit' => ''];
        $direction = $this->alert_type === self::TYPE_THRESHOLD_HIGH ? 'di atas' : 'di bawah';

        return sprintf(
            '%s %s%s %s ambang %s%s',
            $info['label'],
            self::formatNumber($this->recorded_value),
            $info['unit'],
            $direction,
            self::formatNumber($this->threshold_value),
            $info['unit'],
        );
    }

    private static function formatNumber(mixed $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    }
}
