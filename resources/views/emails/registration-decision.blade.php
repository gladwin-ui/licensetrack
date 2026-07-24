<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $approved ? 'Pendaftaran Disetujui' : 'Pendaftaran Ditolak' }} - LicenseTrack</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; color: #334155; margin: 0; padding: 0; }
        .container { max-width: 580px; margin: 40px auto; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .header { background-color: #0b0f19; padding: 24px; text-align: center; }
        .logo { font-size: 20px; font-weight: bold; color: #ffffff; letter-spacing: 0.5px; }
        .content { padding: 40px; }
        .title { font-size: 20px; font-weight: bold; color: #0f172a; margin-top: 0; margin-bottom: 16px; }
        .body-text { font-size: 14px; color: #64748b; line-height: 1.6; margin-bottom: 24px; }
        .button { display: inline-block; background-color: #0b0f19; color: #ffffff !important; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-size: 14px; font-weight: 600; }
        .alert-box { background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 16px 20px; color: #b91c1c; font-size: 13px; margin: 24px 0; }
        .footer { background-color: #f8fafc; padding: 16px; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px solid #f1f5f9; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <span class="logo">LicenseTrack</span>
        </div>
        <div class="content">
            @if ($approved)
                <h1 class="title">Pendaftaran Anda Disetujui</h1>
                <p class="body-text">Halo, {{ $user->name }}</p>
                <p class="body-text">
                    Pendaftaran akun administrator Anda di LicenseTrack telah <strong>disetujui</strong> oleh admin utama.
                    Anda sekarang dapat masuk menggunakan email dan kata sandi yang Anda daftarkan.
                </p>
                <p style="text-align: center; margin: 32px 0;">
                    <a href="{{ route('login') }}" class="button">Masuk ke LicenseTrack</a>
                </p>
            @else
                <h1 class="title">Pendaftaran Anda Ditolak</h1>
                <p class="body-text">Halo, {{ $user->name }}</p>
                <div class="alert-box">
                    Mohon maaf, pengajuan akun administrator Anda di LicenseTrack <strong>ditolak</strong> oleh admin utama.
                    Jika Anda merasa ini keliru, silakan hubungi administrator perusahaan.
                </div>
            @endif
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} LicenseTrack &middot; {{ setting('company_name', config('reminder.company_name', 'PT Hariff Dipa Persada')) }}
        </div>
    </div>
</body>
</html>
