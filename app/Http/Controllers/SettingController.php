<?php

namespace App\Http\Controllers;

use App\Models\License;
use App\Models\Setting;
use App\Models\WhatsappMessage;
use App\Services\AuditLogger;
use App\Services\ReminderScheduler;
use App\Services\WhatsApp\FonnteDeviceChecker;
use App\Services\WhatsApp\FonnteGateway;
use App\Services\WhatsApp\WhatsAppGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class SettingController extends Controller
{
    public function index()
    {
        // Get cached device status (no API call)
        $fonnteDevice = FonnteDeviceChecker::getCached();

        // If no cache but we have a token, try to load from persisted DB fields
        if ($fonnteDevice === null && filled(FonnteDeviceChecker::getActiveToken())) {
            $connected = \App\Models\Setting::find('fonnte_device_connected')?->value;
            if ($connected !== null) {
                $fonnteDevice = [
                    'valid'       => true,
                    'device'      => \App\Models\Setting::find('fonnte_device_number')?->value,
                    'connected'   => $connected === '1',
                    'name'        => \App\Models\Setting::find('fonnte_device_name')?->value,
                    'package'     => \App\Models\Setting::find('fonnte_device_package')?->value,
                    'quota'       => (int) (\App\Models\Setting::find('fonnte_device_quota')?->value ?? 0),
                    'used'        => (int) (\App\Models\Setting::find('fonnte_device_used')?->value ?? 0),
                    'expired_at'  => \App\Models\Setting::find('fonnte_device_expired')?->value,
                    'error'       => null,
                ];
            }
        }

        // Masked token display: show only last 6 chars
        $storedEncrypted = \App\Models\Setting::find('fonnte_token')?->value;
        $hasDbToken = filled($storedEncrypted);
        $envToken = config('whatsapp.fonnte_token');

        if ($hasDbToken) {
            try {
                $plainToken = \Illuminate\Support\Facades\Crypt::decryptString($storedEncrypted);
                $maskedFonnteToken = '••••••' . substr($plainToken, -6);
            } catch (\Exception $e) {
                $maskedFonnteToken = '(token rusak — tempel ulang)';
            }
        } elseif (filled($envToken)) {
            $maskedFonnteToken = '••••••' . substr($envToken, -6) . ' (dari .env)';
        } else {
            $maskedFonnteToken = null;
        }

        $checkedAt = \App\Models\Setting::find('fonnte_device_checked_at')?->value;

        return view('settings.index', compact('fonnteDevice', 'maskedFonnteToken', 'checkedAt'));
    }

    public function update(Request $request, ReminderScheduler $scheduler)
    {
        $validated = $request->validate([
            'reminder_send_time'    => 'required|date_format:H:i',
            'reminder_company_name' => 'required|string|max:255',
            'wa_gateway'            => 'required|in:log,meta,fonnte',
        ]);

        $oldSendTime = setting('reminder_send_time');

        foreach ($validated as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
            Cache::forget('setting_' . $key);
        }

        // Check if send time changed, regenerate schedules
        if ($oldSendTime !== $validated['reminder_send_time']) {
            $activeLicenses = License::whereIn('status', ['active', 'renewed'])->get();
            foreach ($activeLicenses as $license) {
                $scheduler->generateFor($license);
            }

            AuditLogger::log('settings.updated', null, "Pengaturan sistem diperbarui (Jam kirim diubah). Gateway: {$validated['wa_gateway']}, Perusahaan: {$validated['reminder_company_name']}, Jam Kirim: {$validated['reminder_send_time']}.");

            return redirect()->route('settings.index')
                ->with('status', 'Pengaturan berhasil disimpan. Jam kirim reminder telah diubah, jadwal pending disesuaikan ulang.');
        }

        AuditLogger::log('settings.updated', null, "Pengaturan sistem diperbarui. Gateway: {$validated['wa_gateway']}, Perusahaan: {$validated['reminder_company_name']}, Jam Kirim: {$validated['reminder_send_time']}.");

        return redirect()->route('settings.index')
            ->with('status', 'Pengaturan berhasil disimpan.');
    }

    public function sendTest(Request $request, WhatsAppGateway $gateway)
    {
        $validated = $request->validate([
            'phone'    => 'required|string|max:20',
            'template' => 'required|in:reminder,expired',
        ]);

        $phone = $validated['phone'];
        $templateType = $validated['template'];

        if ($gateway instanceof FonnteGateway) {
            $companyName = setting('reminder_company_name', 'PT Hariff');
            $nowStr = now()->translatedFormat('d F Y H:i');

            if ($templateType === 'expired') {
                $bodyText = "⚠️ *[TEST] PERINGATAN KEDALUWARSA LISENSI* ⚠️\n\n"
                          . "Halo *Budi Santoso*,\n\n"
                          . "Pemberitahuan penting dari *{$companyName}* bahwa lisensi di bawah tanggung jawab Anda *TELAH KEDALUWARSA*:\n\n"
                          . "📋 *INFORMASI LISENSI*\n"
                          . "• *Judul:* [TEST] Microsoft 365 Business Standard\n"
                          . "• *Vendor / Penyedia:* Microsoft Corporation\n"
                          . "• *License Key:* TEST-CERT-EXPIRED-2026\n"
                          . "• *Masa Berlaku:* 14 Juli 2025 s/d 11 Juli 2026\n"
                          . "• *Keterlambatan:* Terlewat 3 hari yang lalu\n"
                          . "• *Status:* Aktif\n"
                          . "• *Deskripsi:* Pesan simulasi uji coba untuk lisensi yang sudah kedaluwarsa.\n\n"
                          . "Mohon *SEGERA* menindaklanjuti pembaruan/perpanjangan lisensi ini karena dapat mengganggu aktivitas operasional perusahaan.\n\n"
                          . "Terima kasih.\n"
                          . "_(Waktu uji coba: {$nowStr} WIB)_";
            } else {
                $bodyText = "Halo *Budi Santoso*,\n\n"
                          . "Berikut adalah pengingat dari *{$companyName}* mengenai lisensi yang berada di bawah tanggung jawab Anda:\n\n"
                          . "📋 *INFORMASI LISENSI*\n"
                          . "• *Judul:* [TEST] Antivirus Kaspersky Endpoint Security\n"
                          . "• *Vendor / Penyedia:* Kaspersky Lab\n"
                          . "• *License Key:* TEST-CERT-ACTIVE-2026\n"
                          . "• *Masa Berlaku:* 14 Juli 2025 s/d 21 Juli 2026\n"
                          . "• *Sisa Waktu:* 7 hari lagi\n"
                          . "• *Status:* Aktif\n"
                          . "• *Deskripsi:* Pesan simulasi uji coba untuk pengingat masa berlaku lisensi.\n\n"
                          . "Mohon segera mengkoordinasikan dan menindaklanjuti proses perpanjangan sebelum tanggal kedaluwarsa agar operasional perusahaan tetap berjalan lancar.\n\n"
                          . "Terima kasih.\n"
                          . "_(Waktu uji coba: {$nowStr} WIB)_";
            }

            $result = $gateway->send($phone, '', [], $bodyText);
        } else {
            // Determine template name
            $templateName = $templateType === 'expired' 
                ? config('whatsapp.templates.expired') 
                : config('whatsapp.templates.reminder');

            // Dummy parameters
            if ($templateType === 'expired') {
                $params = [
                    "Budi Santoso", 
                    setting('reminder_company_name', 'PT Hariff'), 
                    "Dummy License Expired", 
                    now()->translatedFormat('d F Y')
                ];
            } else {
                $params = [
                    "Budi Santoso", 
                    setting('reminder_company_name', 'PT Hariff'), 
                    "Dummy License Reminder", 
                    now()->addDays(30)->translatedFormat('d F Y'),
                    "30"
                ];
            }

            $result = $gateway->send($phone, $templateName, $params);
            $bodyText = "TEST MESSAGE ({$templateType})";
        }

        // Record it to whatsapp_messages for history
        WhatsappMessage::create([
            'phone'              => $phone,
            'body'               => $bodyText,
            'status'             => $result['success'] ? 'sent' : 'failed',
            'wamid'              => $result['wamid'],
            'error_message'      => $result['error'],
            'sent_at'            => $result['success'] ? now() : null,
        ]);

        AuditLogger::log('settings.test_wa_sent', null, "Uji coba pengiriman WhatsApp " . ($result['success'] ? 'sukses' : 'gagal') . " ke nomor {$phone}.");

        if ($result['success']) {
            return redirect()->route('settings.index')
                ->with('status', "Pesan test berhasil dikirim. WAMID: {$result['wamid']}");
        }

        return redirect()->route('settings.index')
            ->with('error', "Gagal mengirim pesan test. Error: {$result['error']}");
    }

    /**
     * Update the Fonnte API token stored in settings (encrypted).
     * Validates the token against the real Fonnte API before saving.
     */
    public function updateFonnteToken(Request $request, FonnteDeviceChecker $checker)
    {
        $request->validate([
            'fonnte_token' => 'nullable|string|max:500',
        ]);

        $newToken = trim($request->input('fonnte_token', ''));

        // If field is left blank, keep existing token — do nothing
        if ($newToken === '') {
            return redirect()->route('settings.index')
                ->with('status', 'Token tidak diubah karena field dikosongkan.');
        }

        // Validate token against Fonnte API before saving
        $deviceInfo = $checker->check($newToken);

        if (!$deviceInfo['valid']) {
            return redirect()->route('settings.index')
                ->withErrors(['fonnte_token' => 'Token tidak valid: ' . ($deviceInfo['error'] ?? 'Ditolak Fonnte.')]);
        }

        // Token is valid — save encrypted to settings
        $encrypted = Crypt::encryptString($newToken);
        Setting::updateOrCreate(['key' => 'fonnte_token'], ['value' => $encrypted]);
        Cache::forget('setting_fonnte_token');

        // Also cache & persist device info
        Cache::put(FonnteDeviceChecker::CACHE_KEY, $deviceInfo, FonnteDeviceChecker::CACHE_TTL);
        Setting::updateOrCreate(['key' => 'fonnte_device_number'],     ['value' => $deviceInfo['device'] ?? '']);
        Setting::updateOrCreate(['key' => 'fonnte_device_name'],       ['value' => $deviceInfo['name'] ?? '']);
        Setting::updateOrCreate(['key' => 'fonnte_device_package'],    ['value' => $deviceInfo['package'] ?? '']);
        Setting::updateOrCreate(['key' => 'fonnte_device_quota'],      ['value' => (string) ($deviceInfo['quota'] ?? '')]);
        Setting::updateOrCreate(['key' => 'fonnte_device_used'],       ['value' => (string) ($deviceInfo['used'] ?? '')]);
        Setting::updateOrCreate(['key' => 'fonnte_device_expired'],    ['value' => $deviceInfo['expired_at'] ?? '']);
        Setting::updateOrCreate(['key' => 'fonnte_device_connected'],  ['value' => $deviceInfo['connected'] ? '1' : '0']);
        Setting::updateOrCreate(['key' => 'fonnte_device_checked_at'], ['value' => now()->toIso8601String()]);

        // Audit log — log device number only, never the token value
        $deviceNumber = $deviceInfo['device'] ?? 'tidak diketahui';
        AuditLogger::log(
            'settings.fonnte_token_updated',
            null,
            "Mengganti token Fonnte, nomor pengirim baru: {$deviceNumber}."
        );

        $warningMsg = '';
        if (!$deviceInfo['connected']) {
            $warningMsg = ' Perhatian: Device tidak terhubung (WhatsApp offline). Pastikan HP menyala.';
        }

        return redirect()->route('settings.index')
            ->with('status', "Token Fonnte berhasil disimpan. Nomor pengirim terdeteksi: {$deviceNumber}.{$warningMsg}");
    }

    /**
     * Re-check Fonnte device status and refresh cache (called by "Cek Ulang" button).
     */
    public function checkFonnteDevice(FonnteDeviceChecker $checker)
    {
        $result = $checker->checkAndCache();

        AuditLogger::log('settings.fonnte_device_checked', null, 'Pengecekan ulang status device Fonnte dilakukan secara manual.');

        if (!$result['valid']) {
            return redirect()->route('settings.index')
                ->with('error', 'Pengecekan device gagal: ' . ($result['error'] ?? 'Kesalahan tidak diketahui.'));
        }

        $status = $result['connected'] ? 'terhubung' : 'TIDAK terhubung';
        return redirect()->route('settings.index')
            ->with('status', "Status device diperbarui. Device {$result['device']} saat ini {$status}.");
    }
}
