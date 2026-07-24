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
        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->text('intro');
            $table->text('closing');
            $table->boolean('is_default')->default(false)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        Schema::table('licenses', function (Blueprint $table) {
            $table->foreignId('message_template_id')->nullable()->constrained('message_templates')->onDelete('restrict');
            $table->text('message_intro')->nullable();
            $table->text('message_closing')->nullable();
        });

        // Seed the default system template
        DB::table('message_templates')->insert([
            'name'        => 'Template Standar',
            'intro'       => 'Berikut adalah pengingat dari *{perusahaan}* mengenai lisensi yang berada di bawah tanggung jawab Anda:',
            'closing'     => 'Mohon segera mengkoordinasikan dan menindaklanjuti proses perpanjangan sebelum tanggal kedaluwarsa agar operasional perusahaan tetap berjalan lancar.',
            'is_default'  => true,
            'created_by'  => null,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->dropForeign(['message_template_id']);
            $table->dropColumn(['message_template_id', 'message_intro', 'message_closing']);
        });

        Schema::dropIfExists('message_templates');
    }
};
