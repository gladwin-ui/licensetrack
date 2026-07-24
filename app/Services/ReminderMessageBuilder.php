<?php

namespace App\Services;

use App\Models\License;
use App\Models\LicenseContact;
use Carbon\Carbon;

class ReminderMessageBuilder
{
    /**
     * Build the message payload and full text body for a reminder.
     *
     * @param License $license
     * @param LicenseContact $contact
     * @param int $milestone
     * @return array{template: string, parameters: array, body: string}
     */
    public function build(License $license, LicenseContact $contact, int $milestone): array
    {
        Carbon::setLocale('id');

        $companyName = setting('reminder_company_name', config('reminder.company_name', 'Perusahaan'));
        $picName = $contact->name;
        $licenseName = $license->name;
        $endDateFormatted = $license->end_date ? $license->end_date->translatedFormat('d F Y') : '-';
        $days = abs($milestone);

        $parameters = [
            $picName,
            $companyName,
            $licenseName,
            $endDateFormatted,
            (string) $days,
        ];

        $template = $milestone >= 0 ? 'license_expiry_reminder' : 'license_expired_alert';
        $body = $this->buildText($contact, $license, $milestone);

        return [
            'template'   => $template,
            'parameters' => $parameters,
            'body'       => $body,
        ];
    }

    /**
     * Build the full text message for gateways that do not use templates (e.g. Fonnte).
     *
     * @param LicenseContact $contact
     * @param License $license
     * @param int $milestone
     * @return string
     */
    public function buildText(LicenseContact $contact, License $license, int $milestone): string
    {
        Carbon::setLocale('id');

        $companyName = setting('company_name', config('reminder.company_name', 'PT Hariff Dipa Persada'));
        $picName = $contact->name;
        $licenseName = $license->name;

        $vendorName  = $license->vendor ?: '-';
        $licenseKey  = $license->license_key ?: '-';
        $startFormat = $license->start_date ? $license->start_date->translatedFormat('d F Y') : '-';
        $endFormat   = $license->end_date ? $license->end_date->translatedFormat('d F Y') : '-';
        $description = $license->description ?: '-';

        $statusMap = [
            'active'    => 'Aktif',
            'renewed'   => 'Diperbarui',
            'cancelled' => 'Dibatalkan',
        ];
        $statusIndo = $statusMap[$license->status] ?? ucfirst((string) $license->status);

        $daysRemainingAbs = abs($license->daysRemaining);

        if ($milestone >= 0) {
            // Resolve custom or template intro/closing
            if (filled($license->message_intro) && filled($license->message_closing)) {
                $introRaw = $license->message_intro;
                $closingRaw = $license->message_closing;
            } elseif ($license->message_template_id && $license->messageTemplate) {
                $introRaw = $license->messageTemplate->intro;
                $closingRaw = $license->messageTemplate->closing;
            } else {
                $defaultTemplate = \App\Models\MessageTemplate::where('is_default', true)->first();
                $introRaw = $defaultTemplate ? $defaultTemplate->intro : 'Berikut adalah pengingat dari *{perusahaan}* mengenai lisensi yang berada di bawah tanggung jawab Anda:';
                $closingRaw = $defaultTemplate ? $defaultTemplate->closing : 'Mohon segera mengkoordinasikan dan menindaklanjuti proses perpanjangan sebelum tanggal kedaluwarsa agar operasional perusahaan tetap berjalan lancar.';
            }

            $introResolved = \App\Services\MessagePlaceholderResolver::resolve($introRaw, $license, $contact);
            $closingResolved = \App\Services\MessagePlaceholderResolver::resolve($closingRaw, $license, $contact);

            return "Halo *{$picName}*,\n\n"
                 . "{$introResolved}\n\n"
                 . "📋 *INFORMASI LISENSI*\n"
                 . "• *Judul:* {$licenseName}\n"
                 . "• *Vendor / Penyedia:* {$vendorName}\n"
                 . "• *License Key:* {$licenseKey}\n"
                 . "• *Masa Berlaku:* {$startFormat} s/d {$endFormat}\n"
                 . "• *Sisa Waktu:* {$daysRemainingAbs} hari lagi\n"
                 . "• *Status:* {$statusIndo}\n"
                 . "• *Deskripsi:* {$description}\n\n"
                 . "{$closingResolved}\n\n"
                 . "Terima kasih.";
        } else {
            return "⚠️ *PERINGATAN KEDALUWARSA LISENSI* ⚠️\n\n"
                 . "Halo *{$picName}*,\n\n"
                 . "Pemberitahuan penting dari *{$companyName}* bahwa lisensi di bawah tanggung jawab Anda *TELAH KEDALUWARSA*:\n\n"
                 . "📋 *INFORMASI LISENSI*\n"
                 . "• *Judul:* {$licenseName}\n"
                 . "• *Vendor / Penyedia:* {$vendorName}\n"
                 . "• *License Key:* {$licenseKey}\n"
                 . "• *Masa Berlaku:* {$startFormat} s/d {$endFormat}\n"
                 . "• *Keterlambatan:* Terlewat {$daysRemainingAbs} hari yang lalu\n"
                 . "• *Status:* {$statusIndo}\n"
                 . "• *Deskripsi:* {$description}\n\n"
                 . "Mohon *SEGERA* menindaklanjuti pembaruan/perpanjangan lisensi ini karena dapat mengganggu aktivitas operasional perusahaan.\n\n"
                 . "Terima kasih.";
        }
    }
}

