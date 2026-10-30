<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Get IDs of all soft-deleted licenses
        $deletedLicenseIds = DB::table('licenses')
            ->whereNotNull('deleted_at')
            ->pluck('id')
            ->toArray();

        if (!empty($deletedLicenseIds)) {
            // Update lifecycle_status to stopped for soft-deleted licenses
            DB::table('licenses')
                ->whereIn('id', $deletedLicenseIds)
                ->where('lifecycle_status', '!=', 'stopped')
                ->update(['lifecycle_status' => 'stopped']);

            // Skip any pending/queued reminder logs for soft-deleted licenses
            DB::table('reminder_logs')
                ->whereIn('license_id', $deletedLicenseIds)
                ->whereIn('status', ['pending', 'scheduled', 'queued'])
                ->update(['status' => 'skipped']);

            // Skip any pending/queued urgent alert logs for soft-deleted licenses
            DB::table('urgent_alert_logs')
                ->whereIn('license_id', $deletedLicenseIds)
                ->whereIn('status', ['pending', 'queued'])
                ->update(['status' => 'skipped']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op rollback as this is a data consistency fix
    }
};

