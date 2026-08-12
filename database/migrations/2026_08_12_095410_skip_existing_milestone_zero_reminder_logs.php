<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('reminder_logs')
            ->where('milestone', 0)
            ->whereIn('status', ['pending', 'scheduled', 'queued'])
            ->update(['status' => 'skipped']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $now = \Carbon\Carbon::now('Asia/Jakarta');
        DB::table('reminder_logs')
            ->where('milestone', 0)
            ->where('status', 'skipped')
            ->where('scheduled_at', '>', $now)
            ->update(['status' => 'pending']);
    }
};
