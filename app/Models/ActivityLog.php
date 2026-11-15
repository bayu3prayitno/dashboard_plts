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
        $key = $subject->getKey();
        $properties = ['before' => $before, 'after' => $after];

        // subject_id berupa bigint. Model dengan primary key string (mis. Setting: key) disimpan sebagai 0,
        // dan kuncinya disimpan di properties.
        if (! is_numeric($key)) {
            $properties['subject_key'] = (string) $key;
            $key = 0;
        }

        return static::create([
            'user_id' => $userId,
            'action' => $action,
            'subject_type' => $subject::class,
            'subject_id' => (int) $key,
            'properties' => $properties,
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

    /** Nama objek yang diubah dalam bahasa tampilan, mis. "Perangkat". */
    public function subjectLabel(): string
    {
        return match ($this->subject_type) {
            Device::class => 'Perangkat',
            Gateway::class => 'Gateway',
            Threshold::class => 'Ambang batas',
            Setting::class => 'Pengaturan',
            User::class => 'User',
            Alert::class => 'Alert',
            default => class_basename((string) $this->subject_type),
        };
    }

    /** Rincian aktivitas untuk tabel audit log, disusun dari nilai sebelum & sesudah. */
    public function details(): string
    {
        $label = $this->subjectLabel().' '.($this->properties['subject_key'] ?? '#'.$this->subject_id);
        $before = $this->properties['before'] ?? null;
        $after = $this->properties['after'] ?? null;

        if ($before === null && $after !== null) {
            return "Membuat {$label}";
        }

        if ($before !== null && $after === null) {
            return "Menghapus {$label}";
        }

        $hidden = ['created_at', 'updated_at', 'password', 'remember_token'];
        $changes = [];

        foreach ($after ?? [] as $key => $value) {
            if (in_array($key, $hidden, true)) {
                continue;
            }

            $old = $before[$key] ?? null;
            if ((string) $old !== (string) $value) {
                $changes[] = $key.': '.($old ?? '-').' → '.($value ?? '-');
            }
        }

        if ($changes === []) {
            return "Mengubah {$label}";
        }

        return "Mengubah {$label} (".implode('; ', array_slice($changes, 0, 3)).')';
    }
}
