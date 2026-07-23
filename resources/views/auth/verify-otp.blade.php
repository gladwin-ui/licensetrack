<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Verifikasi OTP — LicenseTrack | {{ setting('company_name', config('reminder.company_name', 'PT Hariff Dipa Persada')) }}</title>
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
        <div class="hidden md:flex md:w-[42%] bg-cover bg-center p-10 flex-col justify-between relative overflow-hidden" style="background-image: url('{{ asset('foto_gedung.avif') }}');">
            <!-- Dark gradient overlay for readability -->
            <div class="absolute inset-0 bg-gradient-to-b from-slate-950/60 via-slate-950/80 to-black z-0"></div>
            <div class="absolute inset-0 opacity-10 z-0">
                <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg" width="100%" height="100%">
                    <defs>
                        <pattern id="grid" width="24" height="24" patternUnits="userSpaceOnUse">
                            <path d="M 24 0 L 0 0 0 24" fill="none" stroke="white" stroke-width="1" />
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#grid)" />
                </svg>
            </div>
            <div class="absolute -top-40 -left-40 w-96 h-96 bg-red-600/10 rounded-full blur-3xl z-0"></div>

            <div class="relative z-10">
                <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur rounded-lg px-3 py-1.5 border border-white/10">
                    <img src="{{ asset('logo cuma diamond.png') }}" alt="Hariff" class="w-4 h-4 object-contain brightness-110">
                    <span class="text-[10px] font-bold text-white tracking-widest uppercase">HARIFF DIPA PERSADA</span>
                </div>
            </div>

            <div class="relative z-10 space-y-6">
                <div>
                    <h2 class="text-2xl font-bold text-white tracking-tight">Verifikasi OTP</h2>
                    <p class="text-xs text-slate-400 mt-1.5 leading-relaxed">
                        Kami telah mengirimkan kode keamanan sekali pakai untuk menjaga akun Anda.
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

            <div class="my-auto space-y-6" x-data="{ otp: '' }">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900 tracking-tight">Verifikasi OTP</h2>
                    <p class="text-xs text-slate-400 mt-1">
                        Masukkan 6 digit kode keamanan yang dikirimkan ke 
                        <strong class="text-slate-700 font-mono">
                            {{ $channel === 'email' ? $email : 'Nomor WhatsApp Anda' }}
                        </strong>.
                    </p>
                </div>

                <!-- Session Status / Info -->
                @if (session('status'))
                    <div class="text-sm text-emerald-600 bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3">
                        {{ session('status') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-xl px-4 py-3">
                        {{ session('error') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.otp.verify.post') }}" @submit="loading = true" class="space-y-5">
                    @csrf

                    <!-- OTP Code Input -->
                    <div>
                        <label for="otp" class="block text-sm font-medium text-slate-700 mb-1.5">Kode OTP (6 Digit)</label>
                        <input id="otp" name="otp" type="text" maxlength="6" autocomplete="one-time-code" required
                               x-model="otp"
                               @input="otp = otp.replace(/\D/g, '')"
                               placeholder="123456"
                               class="block w-full text-center tracking-[1em] font-mono text-2xl px-4 py-3 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-200 focus:outline-none focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 transition">
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" :disabled="loading || otp.length < 6"
                            class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-sm font-semibold text-white bg-slate-900 hover:bg-slate-800 disabled:bg-slate-200 disabled:text-slate-400 disabled:cursor-not-allowed active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-slate-700 focus:ring-offset-2 transition-all shadow-sm">
                        <svg x-show="loading" x-cloak class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="loading ? 'Memverifikasi...' : 'Verifikasi OTP'">Verifikasi OTP</span>
                    </button>
                </form>

                <!-- Back / Change Channel -->
                <div class="flex items-center justify-center">
                    <form method="POST" action="{{ route('password.otp.cancel') }}">
                        @csrf
                        <button type="submit" class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1 transition">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                            </svg>
                            Ganti Kanal / Hubungi Email Ulang
                        </button>
                    </form>
                </div>
            </div>

            <div class="pt-6 mt-6 border-t border-slate-100 text-center text-xs text-slate-400">
                OTP berlaku 10 menit. Maksimal 3 kali percobaan salah.
            </div>
        </div>

    </div>

</body>
</html>
