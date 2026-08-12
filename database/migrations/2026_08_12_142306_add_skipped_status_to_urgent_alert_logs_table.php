<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('urgent_alert_logs', function (Blueprint $table) {
            $table->enum('status', ['pending', 'sent', 'failed', 'skipped'])
                ->default('pending')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('urgent_alert_logs', function (Blueprint $table) {
            $table->enum('status', ['pending', 'sent', 'failed'])
                ->default('pending')
                ->change();
        });
    }
};
