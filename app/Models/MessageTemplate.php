<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'intro', 'closing', 'is_default', 'created_by', 'type'])]
class MessageTemplate extends Model
{
    use HasFactory;

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class, 'message_template_id');
    }

    public function overdueLicenses(): HasMany
    {
        return $this->hasMany(License::class, 'overdue_message_template_id');
    }
}
