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
        $oldVariants = [
            'PT Hariff',
            'PT Hariff Daya Tunggal Engineering',
            '',
        ];

        // Process 'company_name'
        $companyNameSetting = \DB::table('settings')->where('key', 'company_name')->first();
        if ($companyNameSetting) {
            if (in_array(trim($companyNameSetting->value), $oldVariants)) {
                \DB::table('settings')->where('key', 'company_name')->update([
                    'value' => 'PT Hariff Dipa Persada',
                    'updated_at' => now(),
                ]);
            }
        } else {
            \DB::table('settings')->insert([
                'key' => 'company_name',
                'value' => 'PT Hariff Dipa Persada',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Process 'reminder_company_name'
        $reminderCompanySetting = \DB::table('settings')->where('key', 'reminder_company_name')->first();
        if ($reminderCompanySetting) {
            if (in_array(trim($reminderCompanySetting->value), $oldVariants)) {
                \DB::table('settings')->where('key', 'reminder_company_name')->update([
                    'value' => 'PT Hariff Dipa Persada',
                    'updated_at' => now(),
                ]);
            }
        } else {
            \DB::table('settings')->insert([
                'key' => 'reminder_company_name',
                'value' => 'PT Hariff Dipa Persada',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Clear cache
        \Illuminate\Support\Facades\Cache::forget('setting_company_name');
        \Illuminate\Support\Facades\Cache::forget('setting_reminder_company_name');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reversible migration is not strictly needed for this data-only change.
    }
};
