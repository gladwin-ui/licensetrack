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
        // 1. Add column
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->foreignId('urgent_alert_log_id')
                ->nullable()
                ->after('reminder_log_id')
                ->constrained('urgent_alert_logs')
                ->nullOnDelete();
        });

        // 2. Backfill existing H-0 messages
        $messages = DB::table('whatsapp_messages')
            ->whereNull('reminder_log_id')
            ->whereNotNull('license_contact_id')
            ->get();

        foreach ($messages as $msg) {
            $contact = DB::table('license_contacts')
                ->where('id', $msg->license_contact_id)
                ->first();

            if ($contact && $msg->sent_at) {
                $sentDate = date('Y-m-d', strtotime($msg->sent_at));

                // Find matching urgent_alert_log
                $alertLog = DB::table('urgent_alert_logs')
                    ->where('license_id', $contact->license_id)
                    ->where('alert_date', $sentDate)
                    ->first();

                if ($alertLog) {
                    DB::table('whatsapp_messages')
                        ->where('id', $msg->id)
                        ->update(['urgent_alert_log_id' => $alertLog->id]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropForeign(['urgent_alert_log_id']);
            $table->dropColumn('urgent_alert_log_id');
        });
    }
};
