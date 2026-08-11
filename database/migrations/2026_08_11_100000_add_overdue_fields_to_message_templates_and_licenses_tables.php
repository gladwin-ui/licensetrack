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
        Schema::table('message_templates', function (Blueprint $table) {
            $table->enum('type', ['reminder', 'overdue'])->default('reminder')->after('name');
            $table->dropUnique('message_templates_name_unique');
            $table->unique(['name', 'type']);
        });

        Schema::table('licenses', function (Blueprint $table) {
            $table->string('overdue_reminder_mode', 20)->default('standard')->after('message_closing');
            $table->foreignId('overdue_message_template_id')->nullable()->after('overdue_reminder_mode')->constrained('message_templates')->onDelete('restrict');
            $table->text('overdue_message_intro')->nullable()->after('overdue_message_template_id');
            $table->text('overdue_message_closing')->nullable()->after('overdue_message_intro');
        });

        // Seed the default system template for overdue
        DB::table('message_templates')->insert([
            'name'        => 'Template Overdue Standar',
            'type'        => 'overdue',
            'intro'       => 'Pemberitahuan penting dari *{perusahaan}* bahwa lisensi di bawah tanggung jawab Anda *TELAH KEDALUWARSA*:',
            'closing'     => 'Mohon *SEGERA* menindaklanjuti pembaruan/perpanjangan lisensi ini karena dapat mengganggu aktivitas operasional perusahaan.',
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
        DB::table('message_templates')->where('type', 'overdue')->where('is_default', true)->delete();

        Schema::table('licenses', function (Blueprint $table) {
            $table->dropForeign(['overdue_message_template_id']);
            $table->dropColumn([
                'overdue_reminder_mode',
                'overdue_message_template_id',
                'overdue_message_intro',
                'overdue_message_closing'
            ]);
        });

        Schema::table('message_templates', function (Blueprint $table) {
            $table->dropUnique(['name', 'type']);
            $table->unique('name');
            $table->dropColumn('type');
        });
    }
};
