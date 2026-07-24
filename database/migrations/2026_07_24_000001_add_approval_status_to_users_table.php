<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace the boolean `is_active` with an expressive `status` column and
     * add super-admin + approval bookkeeping columns.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('status', ['pending', 'active', 'rejected', 'inactive'])
                ->default('pending')
                ->after('password');
            $table->boolean('is_super_admin')->default(false)->after('status');
            $table->foreignId('approved_by')->nullable()->after('is_super_admin')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });

        // Carry over the old activation flag.
        DB::table('users')->where('is_active', true)->update(['status' => 'active']);
        DB::table('users')->where('is_active', false)->update(['status' => 'inactive']);

        // Existing installs predate the super-admin concept: promote the
        // oldest active admin so the system is never left without one.
        if (! DB::table('users')->where('is_super_admin', true)->exists()) {
            $oldestActiveId = DB::table('users')
                ->where('status', 'active')
                ->orderBy('id')
                ->value('id');

            if ($oldestActiveId !== null) {
                DB::table('users')->where('id', $oldestActiveId)->update(['is_super_admin' => true]);
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('password');
        });

        DB::table('users')->where('status', 'active')->update(['is_active' => true]);
        DB::table('users')->whereIn('status', ['pending', 'rejected', 'inactive'])->update(['is_active' => false]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['status', 'is_super_admin', 'approved_at']);
        });
    }
};
