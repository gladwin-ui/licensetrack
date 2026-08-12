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
        // 1. Drop foreign key and unique constraint
        Schema::table('urgent_alert_logs', function (Blueprint $table) {
            $table->dropForeign(['license_id']);
            $table->dropUnique(['license_id', 'alert_date', 'slot']);
        });

        // 2. Change the slot column length to 10
        Schema::table('urgent_alert_logs', function (Blueprint $table) {
            $table->string('slot', 10)->change();
        });

        // 3. Migrate data
        $logs = DB::table('urgent_alert_logs')
            ->orderBy('alert_date')
            ->orderBy('license_id')
            ->orderBy('slot') // order by old time (e.g. '09:00', '15:00')
            ->get();

        $grouped = [];
        foreach ($logs as $log) {
            $key = $log->license_id . '_' . $log->alert_date;
            if (!isset($grouped[$key])) {
                $grouped[$key] = 0;
            }
            $grouped[$key]++;
            
            $newSlot = 'slot_' . $grouped[$key];
            DB::table('urgent_alert_logs')
                ->where('id', $log->id)
                ->update(['slot' => $newSlot]);
        }

        // 4. Re-add unique constraint and foreign key
        Schema::table('urgent_alert_logs', function (Blueprint $table) {
            $table->unique(['license_id', 'alert_date', 'slot']);
            $table->foreign('license_id')->references('id')->on('licenses')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('urgent_alert_logs', function (Blueprint $table) {
            $table->dropForeign(['license_id']);
            $table->dropUnique(['license_id', 'alert_date', 'slot']);
        });

        Schema::table('urgent_alert_logs', function (Blueprint $table) {
            $table->string('slot', 5)->change();
        });

        // Map back 'slot_1' -> '09:00', 'slot_2' -> '15:00'
        DB::table('urgent_alert_logs')->where('slot', 'slot_1')->update(['slot' => '09:00']);
        DB::table('urgent_alert_logs')->where('slot', 'slot_2')->update(['slot' => '15:00']);

        Schema::table('urgent_alert_logs', function (Blueprint $table) {
            $table->unique(['license_id', 'alert_date', 'slot']);
            $table->foreign('license_id')->references('id')->on('licenses')->cascadeOnDelete();
        });
    }
};
