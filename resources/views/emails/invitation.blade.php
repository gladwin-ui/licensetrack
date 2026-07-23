<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Undangan Administrator LicenseTrack</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; color: #334155; margin: 0; padding: 0; }
        .container { max-width: 580px; margin: 40px auto; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .header { background-color: #0b0f19; padding: 24px; text-align: center; }
        .logo { font-size: 20px; font-weight: bold; color: #ffffff; letter-spacing: 0.5px; }
        .content { padding: 40px; }
        .title { font-size: 20px; font-weight: bold; color: #0f172a; margin-top: 0; margin-bottom: 8px; }
        .greeting { font-size: 15px; color: #334155; margin-bottom: 16px; }
        .body-text { font-size: 14px; color: #64748b; line-height: 1.6; margin-bottom: 24px; }
        .btn-box { text-align: center; margin: 32px 0; }
        .btn { display: inline-block; background-color: #dc2626; color: #ffffff !important; font-weight: 600; font-size: 14px; text-decoration: none; padding: 12px 28px; border-radius: 8px; box-shadow: 0 2px 4px rgba(220, 38, 38, 0.2); }
        .btn:hover { background-color: #b91c1c; }
        .note { font-size: 12px; color: #94a3b8; line-height: 1.5; border-top: 1px solid #f1f5f9; padding-top: 16px; }
        .footer { background-color: #f8fafc; padding: 16px; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px solid #f1f5f9; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <span class="logo">LicenseTrack</span>
        </div>
        <div class="content">
            <h1 class="title">Undangan Registrasi Akun</h1>
            <p class="greeting">Halo, {{ $invitation->name }}</p>
            <p class="body-text">
                Anda telah diundang untuk bergabung sebagai **Administrator** di sistem internal **LicenseTrack - PT Hariff Daya Tunggal Engineering**.
                Silakan terima undangan ini dan selesaikan registrasi akun Anda dengan mengeklik tombol di bawah ini.
            </p>
            
            <div class="btn-box">
                <a href="{{ url('/invitation/' . $invitation->token) }}" class="btn">Terima Undangan &amp; Buat Akun</a>
            </div>

            <p class="body-text">
                Jika tombol di atas tidak dapat diklik, salin dan tempel tautan berikut ke browser Anda:<br>
                <a href="{{ url('/invitation/' . $invitation->token) }}" style="color: #2563eb; text-decoration: underline; word-break: break-all;">
                    {{ url('/invitation/' . $invitation->token) }}
                </a>
            </p>

            <p class="note">Undangan ini rahasia, berlaku selama <strong>48 jam</strong> (hingga {{ $invitation->expires_at->translatedFormat('d F Y H:i') }} WIB), dan hanya dapat digunakan sekali. Setelah itu, Anda perlu meminta administrator pengundang untuk mengirim ulang undangan.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} LicenseTrack &middot; PT Hariff Daya Tunggal Engineering
        </div>
    </div>
</body>
</html>
