<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['device_id', 'packet_no', 'temperature', 'voltage', 'current', 'power', 'irradiance', 'rssi', 'snr', 'packet_loss', 'recorded_at'])]
class Measurement extends Model
{
    /** Tabel ini hanya punya created_at (tidak ada updated_at sesuai ERD). */
    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'temperature' => 'decimal:2',
            'voltage' => 'decimal:2',
            'current' => 'decimal:2',
            'power' => 'decimal:2',
            'irradiance' => 'decimal:2',
            'snr' => 'decimal:2',
            'packet_loss' => 'decimal:2',
            'recorded_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
