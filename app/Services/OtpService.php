<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Mail\OtpMail;
use App\Services\WhatsApp\WhatsAppGateway;
use App\Services\WhatsApp\FonnteDeviceChecker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class OtpService
{
    /**
     * Rate limit: maximum OTP requests in 15 minutes.
     */
    const MAX_REQUESTS_15_MIN = 3;

    /**
     * OTP validity in minutes.
     */
    const OTP_LIFETIME_MINUTES = 10;

    /**
     * Max incorrect code attempts before burning the OTP.
     */
    const MAX_ATTEMPTS = 3;

    /**
     * Generate and send a new OTP code to the identifier (email or phone).
     *
     * @param string $identifier The target email or phone number.
     * @param string $purpose 'invitation' or 'password_reset'.
     * @param string $channel 'email' or 'whatsapp'.
     * @return array{success: bool, error: ?string}
     */
    public function generateAndSend(string $identifier, string $purpose, string $channel): array
    {
        // 1. Check rate limit
        $recentCount = OtpCode::where('identifier', $identifier)
            ->where('purpose', $purpose)
            ->where('created_at', '>=', now()->subMinutes(15))
            ->count();

        if ($recentCount >= self::MAX_REQUESTS_15_MIN) {
            return [
                'success' => false,
                'error'   => 'Batas permintaan OTP terlampaui. Silakan tunggu beberapa saat (maksimal 3 permintaan per 15 menit).',
            ];
        }

        // 2. Generate cryptographically secure 6-digit OTP
        try {
            $code = (string) random_int(100000, 999999);
        } catch (\Exception $e) {
            $code = (string) rand(100000, 999999); // Fallback
        }

        $codeHash = Hash::make($code);
        $expiresAt = now()->addMinutes(self::OTP_LIFETIME_MINUTES);

        // 3. Save to database
        $otp = OtpCode::create([
            'identifier' => $identifier,
            'purpose'    => $purpose,
            'code_hash'  => $codeHash,
            'attempts'   => 0,
            'expires_at' => $expiresAt,
            'created_at' => now(),
        ]);

        $purposeText = $purpose === 'invitation' ? 'Registrasi Undangan' : 'Reset Kata Sandi';

        // 4. Send through selected channel
        if ($channel === 'email') {
            try {
                Mail::to($identifier)->send(new OtpMail($code, $purposeText));
            } catch (\Exception $e) {
                // Burn the OTP if sending failed
                $otp->delete();
                Log::error('OtpService: failed to send email OTP', ['error' => $e->getMessage()]);
                return [
                    'success' => false,
                    'error'   => 'Gagal mengirim email OTP: ' . $e->getMessage(),
                ];
            }
        } elseif ($channel === 'whatsapp') {
            // Verify if WhatsApp is online/available
            if (!$this->isWhatsAppGatewayAvailable()) {
                $otp->delete();
                return [
                    'success' => false,
                    'error'   => 'Layanan WhatsApp Gateway saat ini tidak terhubung. Silakan gunakan kanal Email.',
                ];
            }

            try {
                $waGateway = app(WhatsAppGateway::class);
                $message = "Kode OTP Anda untuk {$purposeText} di LicenseTrack adalah: {$code}. Kode ini rahasia, berlaku 10 menit. JANGAN berikan kode ini kepada siapa pun.";
                
                // Meta needs template, Fonnte & Log support free text.
                // We send via gateway's send method
                $result = $waGateway->send($identifier, 'otp_verification', ['otp' => $code], $message);

                if (empty($result['success'])) {
                    $otp->delete();
                    return [
                        'success' => false,
                        'error'   => 'Gagal mengirim OTP ke WhatsApp: ' . ($result['error'] ?? 'koneksi ditolak gateway.'),
                    ];
                }
            } catch (\Exception $e) {
                $otp->delete();
                Log::error('OtpService: failed to send whatsapp OTP', ['error' => $e->getMessage()]);
                return [
                    'success' => false,
                    'error'   => 'Gagal mengirim OTP ke WhatsApp: ' . $e->getMessage(),
                ];
            }
        } else {
            $otp->delete();
            return [
                'success' => false,
                'error'   => 'Kanal pengiriman OTP tidak didukung.',
            ];
        }

        // Never log the OTP code!
        Log::channel('whatsapp')->info('OtpService: OTP generated and sent successfully', [
            'identifier' => $identifier,
            'purpose'    => $purpose,
            'channel'    => $channel,
        ]);

        return ['success' => true, 'error' => null];
    }

    /**
     * Verify the code entered by the user.
     *
     * @param string $identifier
     * @param string $purpose
     * @param string $enteredCode
     * @return array{valid: bool, error: ?string}
     */
    public function verify(string $identifier, string $purpose, string $enteredCode): array
    {
        $otp = OtpCode::where('identifier', $identifier)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->orderBy('id', 'desc')
            ->first();

        if (!$otp) {
            return [
                'valid' => false,
                'error' => 'Kode OTP tidak ditemukan atau belum pernah diminta.',
            ];
        }

        // Check expiry
        if ($otp->isExpired()) {
            return [
                'valid' => false,
                'error' => 'Kode OTP sudah kedaluwarsa. Silakan minta kode baru.',
            ];
        }

        // Check attempts
        if ($otp->attempts >= self::MAX_ATTEMPTS) {
            return [
                'valid' => false,
                'error' => 'Kode OTP ini telah dibatalkan karena salah memasukkan terlalu banyak. Silakan minta kode baru.',
            ];
        }

        // Verify hash
        if (!Hash::check($enteredCode, $otp->code_hash)) {
            $otp->increment('attempts');
            $remaining = self::MAX_ATTEMPTS - $otp->attempts;

            if ($remaining <= 0) {
                // Burn the OTP
                $otp->update(['expires_at' => now()->subSecond()]);
                return [
                    'valid' => false,
                    'error' => 'Kode OTP salah. Anda telah salah memasukkan kode sebanyak 3 kali. Kode ini sekarang tidak berlaku lagi. Silakan minta kode baru.',
                ];
            }

            return [
                'valid' => false,
                'error' => "Kode OTP salah. Tersisa {$remaining} kesempatan lagi sebelum kode dibatalkan.",
            ];
        }

        // Mark as used
        $otp->update(['used_at' => now()]);

        return ['valid' => true, 'error' => null];
    }

    /**
     * Check if the WhatsApp Gateway is active and connected.
     */
    public function isWhatsAppGatewayAvailable(): bool
    {
        $gateway = setting('wa_gateway', config('whatsapp.gateway', 'log'));

        if ($gateway === 'log') {
            return true; // Dev mode is always available (logs it)
        }

        if ($gateway === 'meta') {
            return filled(config('whatsapp.access_token')); // Meta is active if token is filled
        }

        if ($gateway === 'fonnte') {
            // Check cache or persisted value using getGatewayDot
            $dot = FonnteDeviceChecker::getGatewayDot('fonnte');
            return $dot === 'bg-emerald-500'; // Only available if status is connect
        }

        return false;
    }
}
