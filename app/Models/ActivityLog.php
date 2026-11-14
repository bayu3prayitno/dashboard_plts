<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'action', 'subject_type', 'subject_id', 'properties'])]
class ActivityLog extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    /** Pengguna yang melakukan aksi (NULL untuk aktivitas sistem). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Objek yang diubah, mis. Device atau Threshold. */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
