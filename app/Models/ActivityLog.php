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

    /** Mencatat perubahan ke audit log (PRD 4.5). $before/$after berisi atribut sebelum & sesudah. */
    public static function record(?int $userId, string $action, Model $subject, ?array $before = null, ?array $after = null): self
    {
        return static::create([
            'user_id' => $userId,
            'action' => $action,
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'properties' => ['before' => $before, 'after' => $after],
        ]);
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
