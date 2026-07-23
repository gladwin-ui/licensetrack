<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class License extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'vendor',
        'description',
        'license_key',
        'start_date',
        'end_date',
        'status',
        'created_by',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'end_date'    => 'date',
        'license_key' => 'encrypted',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function files(): HasMany
    {
        return $this->hasMany(LicenseFile::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(LicenseContact::class);
    }

    public function reminderLogs(): HasMany
    {
        return $this->hasMany(ReminderLog::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /**
     * Days remaining until end_date. Can be negative if already expired.
     * Calculated from Carbon::today() in Asia/Jakarta timezone.
     */
    public function getDaysRemainingAttribute(): int
    {
        $today = Carbon::today('Asia/Jakarta');
        return $today->diffInDays($this->end_date, false);
    }

    /**
     * Health status based on days remaining.
     * Returns: 'aman' (>90), 'waspada' (31-90), 'kritis' (1-30), 'expired' (<=0)
     */
    public function getHealthStatusAttribute(): string
    {
        $days = $this->daysRemaining;

        if ($days > 90) {
            return 'aman';
        } elseif ($days >= 31) {
            return 'waspada';
        } elseif ($days >= 1) {
            return 'kritis';
        } else {
            return 'expired';
        }
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Scope: licenses expiring within $days days from today.
     */
    public function scopeExpiringWithin($query, int $days)
    {
        $today = Carbon::today('Asia/Jakarta');
        return $query->whereBetween('end_date', [
            $today,
            $today->copy()->addDays($days),
        ]);
    }

    /**
     * Scope: licenses that have already expired (end_date <= today).
     */
    public function scopeExpired($query)
    {
        return $query->where('end_date', '<=', Carbon::today('Asia/Jakarta'));
    }
}
