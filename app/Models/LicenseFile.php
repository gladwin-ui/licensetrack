<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'license_id',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }
}
