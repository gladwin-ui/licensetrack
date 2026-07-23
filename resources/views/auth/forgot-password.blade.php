<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Lupa Kata Sandi - LicenseTrack</title>
    <link rel="icon" type="image/png" href="{{ asset('logo cuma diamond.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="h-full bg-slate-50 flex items-center justify-center p-4 sm:p-6 lg:p-8 antialiased">

    <div class="w-full max-w-4xl bg-white rounded-2xl shadow-xl border border-slate-200/80 overflow-hidden flex flex-col md:flex-row min-h-[540px]">
        
        <!-- Left Panel: Visual -->
        <div class="hidden md:flex md:w-[42%] bg-gradient-to-br from-slate-900 via-slate-950 to-black p-10 flex-col justify-between relative overflow-hidden">
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg" width="100%" height="100%">
                    <defs>
                        <pattern id="grid" width="24" height="24" patternUnits="userSpaceOnUse">
                            <path d="M 24 0 L 0 0 0 24" fill="none" stroke="white" stroke-width="1" />
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#grid)" />
                </svg>
            </div>
            <div class="absolute -top-40 -left-40 w-96 h-96 bg-red-600/10 rounded-full blur-3xl"></div>

            <div class="relative z-10">
                <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur rounded-lg px-3 py-1.5 border border-white/10">
                    <img src="{{ asset('logo cuma diamond.png') }}" alt="Hariff" class="w-4 h-4 object-contain brightness-110">
                    <span class="text-[10px] font-bold text-white tracking-widest uppercase">HARIFF DEFENSE</span>
                </div>
            </div>

            <div class="relative z-10 space-y-6">
                <div>
                    <h2 class="text-2xl font-bold text-white tracking-tight">Pemulihan Akun</h2>
                    <p class="text-xs text-slate-400 mt-1.5 leading-relaxed">
                        Silakan verifikasi identitas Anda untuk mengatur ulang kata sandi.
                    </p>
                </div>
            </div>
        </div>

        <!-- Right Panel: Form -->
        <div class="flex-1 p-8 sm:p-10 lg:p-12 flex flex-col justify-between" x-data="{ loading: false }">
            
            <!-- Mobile Brand Header -->
            <div class="flex flex-col items-center md:hidden mb-6">
                <div class="flex items-center justify-center w-11 h-11 bg-slate-900 rounded-xl mb-3 shadow">
                    <img src="{{ asset('logo cuma diamond.png') }}" alt="LicenseTrack" class="w-6 h-6 object-contain">
                </div>
                <h1 class="text-lg font-bold text-slate-800 tracking-tight">LicenseTrack</h1>
            </div>

            <div class="my-auto space-y-6">
                <div class="flex items-center gap-3">
                    <a href="{{ route('login') }}" class="flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-500 hover:text-slate-900 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>
                    </a>
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900 tracking-tight">Lupa Kata Sandi</h2>
                        <p class="text-xs text-slate-400 mt-0.5">Masukkan email Anda untuk menerima kode OTP.</p>
                    </div>
                </div>

                @if (session('error'))
                    <div class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-xl px-4 py-3">
                        {{ session('error') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}" @submit="loading = true" class="space-y-4">
                    @csrf

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Alamat Email</label>
                        <input id="email" name="email" type="email" autocomplete="email" required
                               value="{{ old('email') }}" autofocus
                               placeholder="nama@hariff.co.id"
                               class="block w-full px-4 py-2.5 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 transition">
                    </div>

                    <!-- OTP Channel -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Kirim Kode OTP melalui</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="flex items-center justify-between p-3 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 transition">
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0l-7.5-4.615a2.25 2.25 0 01-1.07-1.916V6.75" />
                                    </svg>
                                    <span class="text-sm font-medium text-slate-700">Email</span>
                                </span>
                                <input type="radio" name="channel" value="email" checked class="text-slate-900 focus:ring-slate-500/20">
                            </label>

                            @php
                                $otpService = app(\App\Services\OtpService::class);
                                $waAvailable = $otpService->isWhatsAppGatewayAvailable();
                            @endphp

                            <label class="flex items-center justify-between p-3 border rounded-xl transition {{ $waAvailable ? 'border-slate-200 cursor-pointer hover:bg-slate-50' : 'border-slate-100 bg-slate-50/50 opacity-60 cursor-not-allowed' }}"
                                   title="{{ $waAvailable ? '' : 'Gateway WhatsApp sedang tidak terhubung' }}">
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4 {{ $waAvailable ? 'text-green-500' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                    <span class="text-sm font-medium text-slate-700">WhatsApp</span>
                                </span>
                                <input type="radio" name="channel" value="whatsapp" {{ $waAvailable ? '' : 'disabled' }} class="text-slate-900 focus:ring-slate-500/20">
                            </label>
                        </div>
                        @if(!$waAvailable)
                            <p class="text-[10px] text-red-500 mt-1.5 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                                Saluran WhatsApp tidak aktif (Gateway Offline). Gunakan opsi Email.
                            </p>
                        @endif
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" :disabled="loading"
                            class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-sm font-semibold text-white bg-slate-900 hover:bg-slate-800 disabled:bg-slate-700 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-slate-700 focus:ring-offset-2 transition-all shadow-sm mt-2">
                        <svg x-show="loading" x-cloak class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="loading ? 'Mengirim...' : 'Minta Kode OTP'">Minta Kode OTP</span>
                    </button>
                </form>
            </div>

            <div class="pt-6 mt-6 border-t border-slate-100 text-center text-xs text-slate-400">
                Hubungi administrator jika email Anda tidak terdaftar.
            </div>
        </div>

    </div>

</body>
</html>
