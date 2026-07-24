<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title . ' — ' : '' }}LicenseTrack | {{ setting('company_name', config('reminder.company_name', 'PT Hariff Dipa Persada')) }}</title>
        <link rel="icon" type="image/png" href="{{ asset('logo cuma diamond.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-800 antialiased h-full bg-slate-50">
        <div class="min-h-full flex items-center justify-center p-4 sm:p-6 lg:p-8">
            <div class="w-full max-w-4xl bg-white rounded-2xl shadow-xl border border-slate-200/80 overflow-hidden flex flex-col md:flex-row min-h-[560px]">

                {{-- Left panel: photo + dark gradient overlay + subtle SVG decoration --}}
                <div class="hidden md:flex md:w-[45%] p-10 flex-col justify-between relative overflow-hidden bg-cover bg-center" style="background-image: url('{{ asset('buat_bg.jpeg') }}');">
                    {{-- Dark gradient overlay for readability --}}
                    <div class="absolute inset-0 bg-gradient-to-b from-slate-950/60 via-slate-950/80 to-black z-0"></div>
                    {{-- Grid decorative background --}}
                    <div class="absolute inset-0 opacity-10 z-0">
                        <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg" width="100%" height="100%">
                            <defs>
                                <pattern id="auth-grid" width="24" height="24" patternUnits="userSpaceOnUse">
                                    <path d="M 24 0 L 0 0 0 24" fill="none" stroke="white" stroke-width="1" />
                                </pattern>
                            </defs>
                            <rect width="100%" height="100%" fill="url(#auth-grid)" />
                        </svg>
                    </div>
                    {{-- Soft glow accents --}}
                    <div class="absolute -top-40 -left-40 w-96 h-96 bg-red-600/10 rounded-full blur-3xl z-0"></div>
                    <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-slate-500/10 rounded-full blur-3xl z-0"></div>

                    {{-- Badge: Hariff logo + company name --}}
                    <div class="relative z-10">
                        <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur rounded-lg px-3 py-1.5 border border-white/10">
                            <img src="{{ asset('logo cuma diamond.png') }}" alt="Hariff" class="w-4 h-4 object-contain brightness-110">
                            <span class="text-[10px] font-bold text-white tracking-widest uppercase">HARIFF DIPA PERSADA</span>
                        </div>
                    </div>

                    {{-- Bottom: product name + tagline --}}
                    <div class="relative z-10">
                        <h2 class="text-2xl font-bold text-white tracking-tight">LicenseTrack</h2>
                        <p class="text-xs text-slate-400 mt-1.5 leading-relaxed">
                            Pantau masa berlaku lisensi, jangan sampai terlewat.
                        </p>
                    </div>
                </div>

                {{-- Right panel: form --}}
                <div class="flex-1 p-8 sm:p-10 lg:p-12 flex flex-col">
                    {{-- Mobile brand header (left panel hidden on mobile) --}}
                    <div class="flex flex-col items-center md:hidden mb-6">
                        <div class="flex items-center justify-center w-11 h-11 bg-slate-900 rounded-xl mb-3 shadow">
                            <img src="{{ asset('logo cuma diamond.png') }}" alt="LicenseTrack" class="w-6 h-6 object-contain">
                        </div>
                        <h1 class="text-lg font-bold text-slate-800 tracking-tight">LicenseTrack</h1>
                        <p class="text-[10px] font-semibold text-slate-400 tracking-widest uppercase mt-0.5">Hariff Dipa Persada</p>
                    </div>

                    <div class="my-auto space-y-6">
                        {{ $slot }}
                    </div>
                </div>

            </div>
        </div>
    </body>
</html>
