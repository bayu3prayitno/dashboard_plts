<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['device_id', 'handled_by', 'alert_type', 'sensor_type', 'recorded_value', 'threshold_value', 'status', 'resolution_note', 'triggered_at', 'resolved_at'])]
class Alert extends Model
{
    public const TYPE_OFFLINE = 'offline';

    public const TYPE_THRESHOLD_HIGH = 'threshold_high';

    public const TYPE_THRESHOLD_LOW = 'threshold_low';

    public const STATUS_NEW = 'new';

    public const STATUS_ACKNOWLEDGED = 'acknowledged';

    public const STATUS_RESOLVED = 'resolved';

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

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /** Pengguna yang menangani alert (NULL jika belum ditangani). */
    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
