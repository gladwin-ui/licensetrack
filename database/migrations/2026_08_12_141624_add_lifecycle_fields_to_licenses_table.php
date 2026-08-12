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
        Schema::table('licenses', function (Blueprint $table) {
            $table->enum('lifecycle_status', ['active', 'stopped'])
                ->default('active')
                ->after('status');
            $table->timestamp('stopped_at')->nullable()->after('lifecycle_status');
            $table->foreignId('stopped_by')
                ->nullable()
                ->after('stopped_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->dropForeign(['stopped_by']);
            $table->dropColumn(['lifecycle_status', 'stopped_at', 'stopped_by']);
        });
    }
};
