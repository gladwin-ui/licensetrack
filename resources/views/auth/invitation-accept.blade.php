<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Terima Undangan Administrator — LicenseTrack | {{ setting('company_name', config('reminder.company_name', 'PT Hariff Dipa Persada')) }}</title>
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
                    <h2 class="text-2xl font-bold text-white tracking-tight">Terima Undangan</h2>
                    <p class="text-xs text-slate-400 mt-1.5 leading-relaxed">
                        Bergabunglah sebagai administrator sistem pengingat kedaluwarsa lisensi {{ setting('company_name', config('reminder.company_name', 'PT Hariff Dipa Persada')) }}.
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
                
                <!-- Status Messages -->
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

                @if ($errors->any())
                    <div class="flex items-start gap-2.5 p-4 rounded-xl bg-red-50 border border-red-200 animate-pulse" role="alert">
                        <div class="text-xs text-red-700">
                            <p class="font-semibold mb-0.5">Kesalahan validasi</p>
                            <ul class="space-y-0.5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <!-- ==================== STEP 1: SELECT CHANNEL ==================== -->
                @if ($step === 'select_channel')
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900 tracking-tight">Undangan Administrator</h2>
                        <p class="text-xs text-slate-400 mt-1">Lakukan verifikasi keamanan terlebih dahulu.</p>
                    </div>

                    <div class="space-y-3.5 bg-slate-50 border border-slate-100 rounded-xl p-4 text-sm">
                        <div class="grid grid-cols-3 gap-2">
                            <span class="text-slate-400 font-medium">Nama</span>
                            <span class="col-span-2 text-slate-800 font-semibold">: {{ $invitation->name }}</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <span class="text-slate-400 font-medium">Surel</span>
                            <span class="col-span-2 text-slate-800 font-semibold font-mono">: {{ $invitation->email }}</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <span class="text-slate-400 font-medium">WhatsApp</span>
                            <span class="col-span-2 text-slate-800 font-semibold font-mono">: {{ $invitation->phone }}</span>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('invitation.otp.request', $token) }}" @submit="loading = true" class="space-y-4">
                        @csrf
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
                        </div>

                        <button type="submit" :disabled="loading"
                                class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-sm font-semibold text-white bg-slate-900 hover:bg-slate-800 disabled:bg-slate-700 active:scale-[0.98] transition-all shadow-sm">
                            <svg x-show="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="loading ? 'Mengirim...' : 'Kirim Kode OTP'">Kirim Kode OTP</span>
                        </button>
                    </form>
                @endif


                <!-- ==================== STEP 2: ENTER OTP ==================== -->
                @if ($step === 'enter_otp')
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900 tracking-tight">Masukkan Kode OTP</h2>
                        <p class="text-xs text-slate-400 mt-1">OTP telah dikirimkan untuk verifikasi data Anda.</p>
                    </div>

                    <form method="POST" action="{{ route('invitation.otp.verify', $token) }}" @submit="loading = true" class="space-y-4" x-data="{ code: '' }">
                        @csrf
                        <div>
                            <label for="otp" class="block text-sm font-medium text-slate-700 mb-1.5">Kode OTP (6 Digit)</label>
                            <input id="otp" name="otp" type="text" maxlength="6" autocomplete="one-time-code" required
                                   x-model="code"
                                   @input="code = code.replace(/\D/g, '')"
                                   placeholder="123456"
                                   class="block w-full text-center tracking-[1em] font-mono text-2xl px-4 py-3 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-200 focus:outline-none focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 transition">
                        </div>

                        <button type="submit" :disabled="loading || code.length < 6"
                                class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-sm font-semibold text-white bg-slate-900 hover:bg-slate-800 disabled:bg-slate-200 disabled:text-slate-400 disabled:cursor-not-allowed active:scale-[0.98] transition-all shadow-sm">
                            <svg x-show="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="loading ? 'Memverifikasi...' : 'Verifikasi OTP'">Verifikasi OTP</span>
                        </button>
                    </form>

                    <div class="flex items-center justify-center mt-3">
                        <form method="POST" action="{{ route('invitation.cancel', $token) }}">
                            @csrf
                            <button type="submit" class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1 transition">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                                </svg>
                                Kembali &amp; Pilih Kanal Ulang
                            </button>
                        </form>
                    </div>
                @endif


                <!-- ==================== STEP 3: SET PASSWORD ==================== -->
                @if ($step === 'set_password')
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900 tracking-tight">Tentukan Kata Sandi</h2>
                        <p class="text-xs text-slate-400 mt-1">Buat kata sandi baru untuk mengaktifkan akun Anda.</p>
                    </div>

                    <form method="POST" action="{{ route('invitation.accept', $token) }}" @submit="loading = true" class="space-y-4">
                        @csrf

                        <!-- Password -->
                        <div x-data="{ show: false }">
                            <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Kata Sandi</label>
                            <div class="relative">
                                <input id="password" name="password" :type="show ? 'text' : 'password'"
                                       type="password" required autofocus
                                       placeholder="Minimal 8 karakter"
                                       class="block w-full px-4 py-2.5 pr-11 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 transition">
                                <button type="button" @click="show = !show"
                                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-300 hover:text-slate-500 transition">
                                    <svg x-show="!show" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <svg x-show="show" x-cloak class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Password Confirmation -->
                        <div x-data="{ show: false }">
                            <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1.5">Konfirmasi Kata Sandi</label>
                            <div class="relative">
                                <input id="password_confirmation" name="password_confirmation" :type="show ? 'text' : 'password'"
                                       type="password" required
                                       placeholder="Ulangi kata sandi"
                                       class="block w-full px-4 py-2.5 pr-11 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 transition">
                                <button type="button" @click="show = !show"
                                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-300 hover:text-slate-500 transition">
                                    <svg x-show="!show" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <svg x-show="show" x-cloak class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <button type="submit" :disabled="loading"
                                class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-sm font-semibold text-white bg-slate-900 hover:bg-slate-800 disabled:bg-slate-700 active:scale-[0.98] transition-all shadow-sm mt-2">
                            <svg x-show="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="loading ? 'Menyimpan...' : 'Selesaikan Registrasi'">Selesaikan Registrasi</span>
                        </button>
                    </form>
                @endif

            </div>

            <div class="pt-6 mt-6 border-t border-slate-100 text-center text-xs text-slate-400">
                Pendaftaran diatur sepenuhnya oleh kebijakan keamanan internal {{ setting('company_name', config('reminder.company_name', 'PT Hariff Dipa Persada')) }}.
            </div>
        </div>

    </div>

</body>
</html>
