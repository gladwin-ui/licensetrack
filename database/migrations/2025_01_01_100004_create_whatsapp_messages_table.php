<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reminder_log_id')->nullable()->constrained('reminder_logs')->nullOnDelete();
            $table->foreignId('license_contact_id')->nullable()->constrained('license_contacts')->nullOnDelete();
            $table->string('phone');
            $table->text('body');       // rendered message body
            $table->enum('status', ['pending', 'sent', 'failed']);
            $table->string('wamid')->nullable();        // message id from Meta
            $table->text('error_message')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
