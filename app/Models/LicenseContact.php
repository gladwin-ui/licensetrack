<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LicenseContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'license_id',
        'name',
        'phone',         // stored normalized: 628xxxxxxxxx
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public function whatsappMessages(): HasMany
    {
        return $this->hasMany(WhatsappMessage::class);
    }
}
