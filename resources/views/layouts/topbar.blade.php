@php
    $gateway = setting('wa_gateway', config('whatsapp.gateway', 'log'));
    // Use cached real device status — no API call on every page load
    $gatewayDot = \App\Services\WhatsApp\FonnteDeviceChecker::getGatewayDot($gateway);
    $gatewayConnected = $gatewayDot === 'bg-emerald-500';
    $gatewayFilled = $gateway === 'log' || $gatewayConnected;
    $sentToday = \App\Models\WhatsappMessage::where('status', 'sent')
        ->whereDate('created_at', \Carbon\Carbon::today('Asia/Jakarta'))
        ->count();
@endphp

<header class="min-h-20 py-4 bg-white/80 backdrop-blur-md border-b border-slate-200/80 px-6 sm:px-8 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 z-20 flex-shrink-0">
    {{-- Kiri: Hamburger Button (Mobile) & Page Header --}}
    <div class="flex items-center gap-4 flex-1">
        {{-- Mobile Hamburger Button --}}
        <button type="button" @click="mobileSidebarOpen = !mobileSidebarOpen" 
                class="lg:hidden p-2 rounded-xl text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition" 
                title="Buka Menu">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        @if (isset($header))
            <div class="flex-1">
                {{ $header }}
            </div>
        @endif
    </div>

    {{-- Kanan: Gateway Status Pill + Tanggal/Jam + Akun Dropdown --}}
    <div class="hidden md:flex items-center gap-3 sm:gap-4 flex-shrink-0">
        {{-- Gateway Status Pill (Desktop) — clickable if red/not connected --}}
        @if (!$gatewayConnected && $gateway !== 'log')
            <a href="{{ route('settings.index') }}"
               class="hidden xl:flex items-center gap-2 px-3 py-1.5 bg-red-50 border border-red-200 rounded-full text-xs font-semibold text-red-700 shadow-xs hover:bg-red-100 transition"
               title="Gateway bermasalah — klik untuk ke Pengaturan">
                <span class="h-2 w-2 rounded-full {{ $gatewayDot }}"></span>
                <span>{{ strtoupper($gateway) }}</span>
                <span class="text-red-300">|</span>
                <span class="font-mono">{{ $sentToday }} terkirim</span>
            </a>
        @else
            <div class="hidden xl:flex items-center gap-2 px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-full text-xs font-semibold text-slate-700 shadow-xs" title="Status WhatsApp Gateway">
                <span class="h-2 w-2 rounded-full {{ $gatewayDot }} {{ $gatewayFilled && $gateway !== 'log' ? 'animate-pulse' : '' }}"></span>
                <span>{{ strtoupper($gateway) }}</span>
                <span class="text-slate-300">|</span>
                <span class="text-emerald-600 font-mono">{{ $sentToday }} terkirim</span>
            </div>
        @endif

        {{-- Tanggal + Jam WIB --}}
        <div x-data="{ 
                time: '',
                date: '',
                updateClock() {
                    const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                    const now = new Date();
                    const dayName = days[now.getDay()];
                    const day = String(now.getDate()).padStart(2, '0');
                    const monthName = months[now.getMonth()];
                    const year = now.getFullYear();
                    const hours = String(now.getHours()).padStart(2, '0');
                    const minutes = String(now.getMinutes()).padStart(2, '0');
                    
                    this.date = `${dayName}, ${day} ${monthName} ${year}`;
                    this.time = `${hours}:${minutes} WIB`;
                }
             }" 
             x-init="updateClock(); setInterval(() => updateClock(), 1000)" 
             class="hidden md:flex flex-col text-right text-xs pr-4 border-r border-slate-200">
            <span class="font-semibold text-slate-700" x-text="date"></span>
            <span class="text-slate-400 font-mono mt-0.5" x-text="time"></span>
        </div>

        {{-- User Profile Dropdown --}}
        <div class="hidden lg:flex items-center gap-3 pl-2 sm:pl-4 md:pl-0 md:border-l-0 border-l border-slate-200">
            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <button class="flex items-center gap-2.5 p-1 rounded-full hover:bg-slate-100 transition focus:outline-none">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-red-600 to-red-700 text-white font-extrabold flex items-center justify-center shadow-md text-sm">
                            {{ substr(Auth::user()->name ?? 'U', 0, 1) }}
                        </div>
                        <div class="hidden md:block text-left pr-1">
                            <p class="text-xs font-bold text-slate-800 leading-tight truncate max-w-[120px]">{{ Auth::user()->name }}</p>
                            <p class="text-[10px] font-medium text-slate-400">PT Hariff Dipa</p>
                        </div>
                        <svg class="h-4 w-4 text-slate-400 hidden md:block" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </x-slot>

                <x-slot name="content">
                    <div class="px-4 py-2.5 border-b border-slate-100 text-xs text-slate-500">
                        Masuk sebagai <strong class="text-slate-800 block truncate">{{ Auth::user()->email }}</strong>
                    </div>
                    <x-dropdown-link :href="route('dashboard')">
                        Dashboard
                    </x-dropdown-link>
                    <x-dropdown-link :href="route('profile.edit')">
                        Profil Saya
                    </x-dropdown-link>
                    <x-dropdown-link :href="route('settings.index')">
                        Pengaturan Sistem
                    </x-dropdown-link>
                    <div class="border-t border-slate-100"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();" class="text-red-600 hover:text-red-700">
                            Keluar
                        </x-dropdown-link>
                    </form>
                </x-slot>
            </x-dropdown>
        </div>
    </div>
</header>
