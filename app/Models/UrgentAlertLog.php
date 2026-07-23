<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UrgentAlertLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'license_id',
        'alert_date',
        'slot',
        'status',
        'sent_at',
    ];

    protected $casts = [
        'alert_date' => 'date',
        'sent_at'    => 'datetime',
    ];

    /**
     * Get the license that this alert log belongs to.
     */
    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }
}
