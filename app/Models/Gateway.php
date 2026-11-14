<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['gateway_code', 'name', 'location', 'status', 'mqtt_status', 'internet_status', 'last_seen_at'])]
class Gateway extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
        ];
    }

    /** Perangkat (Node) yang dikelola Gateway ini. */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }
}
