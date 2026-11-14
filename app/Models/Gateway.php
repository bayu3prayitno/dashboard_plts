<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['name', 'location', 'status', 'mqtt_status', 'internet_status', 'last_seen_at'])]
class Gateway extends Model
{
    protected static function booted(): void
    {
        // Kode gateway dibuat otomatis dan tidak bisa diisi dari luar.
        // Saat INSERT dipakai kode sementara yang unik (UUID), lalu setelah ID tersedia
        // diganti dengan kode dari ID: GW-001, GW-002, dst. ID auto-increment selalu unik,
        // sehingga kode tidak mungkin duplikat.
        static::creating(function (Gateway $gateway) {
            $gateway->gateway_code = 'TMP-'.Str::uuid();
        });

        static::created(function (Gateway $gateway) {
            // Atribut diisi langsung (bukan lewat fill) karena gateway_code tidak fillable
            $gateway->gateway_code = 'GW-'.str_pad((string) $gateway->id, 3, '0', STR_PAD_LEFT);
            $gateway->saveQuietly();
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
        ];
    }

    /** Perangkat (Node) yang dikelola Gateway ini. */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }
}
