<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->constrained('licenses')->cascadeOnDelete();
            $table->integer('milestone'); // days before end_date: 90,60,45,30,21,14,7,1,0,-7,-14
            $table->dateTime('scheduled_at');
            $table->enum('status', ['pending', 'queued', 'sent', 'failed', 'skipped']);
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();

            // Idempotency: one reminder per license per milestone
            $table->unique(['license_id', 'milestone']);

            // Performance index for dispatch query
            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_logs');
    }
};
