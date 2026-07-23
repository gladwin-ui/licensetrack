<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Kode OTP LicenseTrack</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; color: #334155; margin: 0; padding: 0; }
        .container { max-width: 580px; margin: 40px auto; background-color: #ffffff; border: 1px border-slate-200; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .header { background-color: #0b0f19; padding: 24px; text-align: center; }
        .logo { font-size: 20px; font-weight: bold; color: #ffffff; letter-spacing: 0.5px; }
        .content { padding: 40px; }
        .title { font-size: 20px; font-weight: bold; color: #0f172a; margin-top: 0; margin-bottom: 8px; text-align: center; }
        .subtitle { font-size: 14px; color: #64748b; margin-top: 0; margin-bottom: 24px; text-align: center; }
        .otp-box { background-color: #f1f5f9; border-radius: 8px; padding: 16px 24px; text-align: center; margin: 24px 0; }
        .otp-code { font-size: 32px; font-weight: bold; letter-spacing: 6px; color: #dc2626; font-family: monospace; }
        .note { font-size: 12px; color: #94a3b8; text-align: center; margin-top: 24px; line-height: 1.5; }
        .footer { background-color: #f8fafc; padding: 16px; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px solid #f1f5f9; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <span class="logo">LicenseTrack</span>
        </div>
        <div class="content">
            <h1 class="title">Kode OTP Verifikasi</h1>
            <p class="subtitle">Silakan gunakan kode di bawah ini untuk memverifikasi proses <strong>{{ $purposeText }}</strong> Anda.</p>
            
            <div class="otp-box">
                <span class="otp-code">{{ $otpCode }}</span>
            </div>

            <p class="note">Kode ini rahasia, berlaku selama <strong>10 menit</strong>, dan hanya dapat digunakan satu kali. Jangan berikan kode ini kepada siapa pun, termasuk staf PT Hariff.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} LicenseTrack &middot; PT Hariff Daya Tunggal Engineering
        </div>
    </div>
</body>
</html>
