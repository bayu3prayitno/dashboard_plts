<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['device_id', 'sensor_type', 'min_value', 'max_value', 'is_active'])]
class Threshold extends Model
{
    public const SENSOR_TYPES = ['temperature', 'voltage', 'current', 'power', 'irradiance'];

    /** Label dan satuan tiap sensor untuk tampilan. */
    public const SENSOR_INFO = [
        'temperature' => ['label' => 'Suhu', 'unit' => '°C'],
        'voltage' => ['label' => 'Tegangan', 'unit' => 'V'],
        'current' => ['label' => 'Arus', 'unit' => 'A'],
        'power' => ['label' => 'Daya', 'unit' => 'W'],
        'irradiance' => ['label' => 'Irradiance', 'unit' => 'W/m²'],
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'min_value' => 'decimal:2',
            'max_value' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /** Null berarti ambang batas standar sistem (global). */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function isGlobal(): bool
    {
        return $this->device_id === null;
    }
}
