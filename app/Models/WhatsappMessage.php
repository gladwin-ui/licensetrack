<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'reminder_log_id',
        'license_contact_id',
        'phone',
        'body',
        'status',
        'wamid',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function reminderLog(): BelongsTo
    {
        return $this->belongsTo(ReminderLog::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(LicenseContact::class, 'license_contact_id');
    }
}
