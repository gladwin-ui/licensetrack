<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Undangan Tidak Valid - LicenseTrack</title>
    <link rel="icon" type="image/png" href="{{ asset('logo cuma diamond.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Inter', sans-serif; }
        .bg-grid {
            background-color: #f1f5f9;
            background-image:
                linear-gradient(rgba(148,163,184,0.15) 1px, transparent 1px),
                linear-gradient(90deg, rgba(148,163,184,0.15) 1px, transparent 1px);
            background-size: 28px 28px;
        }
    </style>
</head>
<body class="h-full bg-grid flex items-center justify-center min-h-screen px-4 py-12 antialiased">

    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-lg border border-slate-200/80 p-8 text-center">
            
            <div class="inline-flex items-center justify-center w-14 h-14 bg-red-50 border border-red-200 rounded-2xl mb-5 text-red-500 shadow-sm">
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
            </div>

            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Undangan Tidak Valid</h1>
            <p class="text-sm text-slate-500 mt-2.5 leading-relaxed">
                {{ $message }}
            </p>

            <div class="mt-8 pt-6 border-t border-slate-100">
                <a href="{{ route('login') }}" 
                   class="inline-flex items-center justify-center w-full px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-slate-900 hover:bg-slate-800 transition shadow-sm">
                    Kembali ke Halaman Login
                </a>
            </div>
        </div>

        <p class="mt-5 text-center text-[11px] text-slate-400">
            &copy; {{ date('Y') }} LicenseTrack &middot; PT Hariff Daya Tunggal Engineering
        </p>
    </div>

</body>
</html>
