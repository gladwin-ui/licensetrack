<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login — LicenseTrack | {{ setting('company_name', config('reminder.company_name', 'PT Hariff Dipa Persada')) }}</title>
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
            <!-- Grid decorative background -->
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
            <!-- Glow effect -->
            <div class="absolute -top-40 -left-40 w-96 h-96 bg-red-600/10 rounded-full blur-3xl z-0"></div>
            <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-slate-500/10 rounded-full blur-3xl z-0"></div>

            <div class="relative z-10">
                <!-- Badge Logo Hariff -->
                <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur rounded-lg px-3 py-1.5 border border-white/10">
                    <img src="{{ asset('logo cuma diamond.png') }}" alt="Hariff" class="w-4 h-4 object-contain brightness-110">
                    <span class="text-[10px] font-bold text-white tracking-widest uppercase">HARIFF DIPA PERSADA</span>
                </div>
            </div>

            <div class="relative z-10 space-y-6">
                <div>
                    <h2 class="text-2xl font-bold text-white tracking-tight">LicenseTrack</h2>
                    <p class="text-xs text-slate-400 mt-1.5 leading-relaxed">
                        Pantau masa berlaku lisensi, jangan sampai terlewat.
                    </p>
                </div>

                <!-- Bullet points -->
                <ul class="space-y-3.5 text-xs text-slate-300 font-medium">
                    <li class="flex items-center gap-2.5">
                        <span class="flex items-center justify-center w-5 h-5 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        </span>
                        <span>Pengingat WhatsApp Otomatis</span>
                    </li>
                    <li class="flex items-center gap-2.5">
                        <span class="flex items-center justify-center w-5 h-5 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        </span>
                        <span>Manajemen Dokumen Terpusat</span>
                    </li>
                    <li class="flex items-center gap-2.5">
                        <span class="flex items-center justify-center w-5 h-5 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        </span>
                        <span>Audit Log Aktivitas Admin</span>
                    </li>
                </ul>
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
                <div>
                    <h2 class="text-2xl font-semibold text-slate-900 tracking-tight">Masuk ke akun</h2>
                    <p class="text-sm text-slate-400 mt-1">Masukkan surel dan kata sandi Anda.</p>
                </div>

                <!-- Session Status -->
                @if (session('status'))
                    <div class="text-sm text-emerald-600 bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3">
                        {{ session('status') }}
                    </div>
                @endif

                <!-- Validation Error Banner -->
                @if ($errors->any())
                    <div class="flex items-start gap-2.5 p-4 rounded-xl bg-red-50 border border-red-200" role="alert">
                        <svg class="w-4 h-4 text-red-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                        <div class="text-xs text-red-700">
                            <p class="font-semibold mb-0.5">Gagal masuk</p>
                            <ul class="space-y-0.5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <!-- Login Form -->
                <form method="POST" action="{{ route('login') }}" @submit="loading = true" class="space-y-4">
                    @csrf

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Surel (Email)</label>
                        <input id="email" name="email" type="email" autocomplete="email" required
                               value="{{ old('email') }}" autofocus
                               placeholder="nama@hariff.co.id"
                               class="block w-full px-4 py-2.5 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 transition">
                    </div>

                    <!-- Password -->
                    <div x-data="{ show: false }">
                        <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Kata Sandi</label>
                        <div class="relative">
                            <input id="password" name="password" :type="show ? 'text' : 'password'"
                                   type="password" autocomplete="current-password" required
                                   placeholder="Masukkan kata sandi"
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

                    <!-- Remember & Forgot Password -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <input id="remember_me" name="remember" type="checkbox"
                                   class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-500/20 focus:ring-offset-0 transition">
                            <label for="remember_me" class="text-sm text-slate-500 select-none cursor-pointer">Ingat saya</label>
                        </div>
                        <a href="{{ route('password.request') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition">
                            Lupa password?
                        </a>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" :disabled="loading"
                            class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-sm font-semibold text-white bg-slate-900 hover:bg-slate-800 disabled:bg-slate-700 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-slate-700 focus:ring-offset-2 transition-all shadow-sm">
                        <!-- Loading spinner -->
                        <svg x-show="loading" x-cloak class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="loading ? 'Memproses...' : 'Masuk'">Masuk</span>
                    </button>
                </form>
            </div>

            <!-- Card Footer note -->
            <div class="pt-6 mt-6 border-t border-slate-100 text-center text-xs text-slate-400">
                Akun hanya dapat dibuat oleh administrator.
            </div>
        </div>

    </div>

</body>
</html>
