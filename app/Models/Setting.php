<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value', 'description'])]
class Setting extends Model
{
    /** Primary key berupa string (kolom key), sesuai ERD. */
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    /** Tabel ini hanya punya updated_at (tidak ada created_at sesuai ERD). */
    public const CREATED_AT = null;
}
