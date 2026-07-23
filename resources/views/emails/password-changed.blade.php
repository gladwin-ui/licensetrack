<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Kata Sandi Diubah - LicenseTrack</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; color: #334155; margin: 0; padding: 0; }
        .container { max-width: 580px; margin: 40px auto; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .header { background-color: #0b0f19; padding: 24px; text-align: center; }
        .logo { font-size: 20px; font-weight: bold; color: #ffffff; letter-spacing: 0.5px; }
        .content { padding: 40px; }
        .title { font-size: 20px; font-weight: bold; color: #0f172a; margin-top: 0; margin-bottom: 16px; }
        .body-text { font-size: 14px; color: #64748b; line-height: 1.6; margin-bottom: 24px; }
        .alert-box { background-color: #fffbeb; border: 1px solid #fef3c7; border-radius: 8px; padding: 16px 20px; color: #b45309; font-size: 13px; margin: 24px 0; }
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
            <h1 class="title">Keamanan Akun Anda</h1>
            <p class="body-text">Halo, {{ $user->name }}</p>
            <p class="body-text">
                Kata sandi untuk akun administrator Anda di **LicenseTrack** baru saja berhasil diperbarui secara mandiri menggunakan verifikasi OTP.
            </p>
            
            <div class="alert-box">
                <strong>Apakah ini Anda?</strong> Jika Anda baru saja melakukan perubahan ini, Anda dapat mengabaikan email ini dengan aman. Namun, jika Anda tidak merasa melakukan tindakan ini, harap hubungi administrator jaringan segera untuk mengamankan akun Anda.
            </div>

            <p class="note">Email notifikasi ini dikirimkan secara otomatis demi menjaga keamanan data perusahaan PT Hariff.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} LicenseTrack &middot; PT Hariff Daya Tunggal Engineering
        </div>
    </div>
</body>
</html>
