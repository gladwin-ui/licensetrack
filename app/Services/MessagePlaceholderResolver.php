<?php

namespace App\Services;

use App\Models\License;
use App\Models\LicenseContact;
use Carbon\Carbon;

class MessagePlaceholderResolver
{
    /**
     * Resolve placeholders in a template string.
     */
    public static function resolve(string $text, License $license, LicenseContact $contact): string
    {
        Carbon::setLocale('id');

        $companyName = setting('company_name', config('reminder.company_name', 'PT Hariff Dipa Persada'));
        $picName = $contact->name;
        $licenseName = $license->name;
        $vendorName = $license->vendor ?: '-';
        $startFormat = $license->start_date ? $license->start_date->translatedFormat('d F Y') : '-';
        $endFormat = $license->end_date ? $license->end_date->translatedFormat('d F Y') : '-';
        
        // Use raw or absolute daysRemaining depending on milestone, max(0) is safe for active reminders
        $daysRemaining = $license->daysRemaining !== null ? (string)$license->daysRemaining : '-';

        $replacements = [
            '{perusahaan}' => $companyName,
            '{nama_pic}' => $picName,
            '{nama_lisensi}' => $licenseName,
            '{vendor}' => $vendorName,
            '{tanggal_mulai}' => $startFormat,
            '{tanggal_berakhir}' => $endFormat,
            '{sisa_hari}' => $daysRemaining,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }

    /**
     * Validate placeholders in a string.
     * Returns an array of invalid placeholders, or empty array if valid.
     */
    public static function validate(string $text): array
    {
        preg_match_all('/\{[^}]+\}/', $text, $matches);
        
        $validPlaceholders = [
            '{perusahaan}',
            '{nama_pic}',
            '{nama_lisensi}',
            '{vendor}',
            '{tanggal_mulai}',
            '{tanggal_berakhir}',
            '{sisa_hari}',
        ];
        
        $invalid = [];
        if (!empty($matches[0])) {
            foreach ($matches[0] as $match) {
                if (!in_array($match, $validPlaceholders, true)) {
                    $invalid[] = $match;
                }
            }
        }
        
        return array_unique($invalid);
    }
}
