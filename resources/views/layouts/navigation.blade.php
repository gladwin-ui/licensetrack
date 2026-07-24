@php
    $gateway = setting('wa_gateway', config('whatsapp.gateway', 'log'));
    // Use cached real device status — no API call on every page load
    $gatewayDot = \App\Services\WhatsApp\FonnteDeviceChecker::getGatewayDot($gateway);
    $gatewayConnected = $gatewayDot === 'bg-emerald-500';
    $gatewayFilled = $gateway === 'log' || $gatewayConnected;
    $sentToday = \App\Models\WhatsappMessage::where('status', 'sent')
        ->whereDate('created_at', \Carbon\Carbon::today('Asia/Jakarta'))
        ->count();
    $licenseCount = \App\Models\License::count();

    $items = [
        [
            'label' => 'Dashboard',
            'route' => 'dashboard',
            'active' => request()->routeIs('dashboard'),
            'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
            'badge' => null
        ],
        [
            'label' => 'Daftar Lisensi',
            'route' => 'licenses.index',
            'active' => request()->routeIs('licenses.*'),
            'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
            'badge' => $licenseCount
        ],
        [
            'label' => 'Riwayat Reminder',
            'route' => 'reminders.index',
            'active' => request()->routeIs('reminders.*'),
            'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
            'badge' => null
        ],
        [
            'label' => 'Pengaturan',
            'route' => 'settings.index',
            'active' => request()->routeIs('settings.*'),
            'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
            'badge' => null
        ],
        ...(Auth::user()->is_super_admin ? [[
            'label' => 'Kelola Admin',
            'route' => 'users.index',
            'active' => request()->routeIs('users.*'),
            'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
            'badge' => ($pendingCount = \App\Models\User::where('status', \App\Models\User::STATUS_PENDING)->count()) > 0 ? $pendingCount : null
        ]] : []),
        [
            'label' => 'Audit Log',
            'route' => 'audit-logs.index',
            'active' => request()->routeIs('audit-logs.*'),
            'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
            'badge' => null
        ],
    ];
@endphp

{{-- DESKTOP DARK SIDEBAR --}}
<aside class="hidden lg:flex lg:flex-col lg:flex-shrink-0 bg-[#0B0F19] text-white shadow-xl z-30 transition-all duration-300"
       :class="sidebarCollapsed ? 'lg:w-20' : 'lg:w-64'">
    {{-- Brand / Logo --}}
    <div class="flex items-center gap-3 h-20 border-b border-slate-800/80 transition-all duration-300"
         :class="sidebarCollapsed ? 'justify-center px-2' : 'px-6'">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group">
            <img src="{{ asset('logo cuma diamond.png') }}" alt="LicenseTrack Logo" class="w-9 h-9 object-contain flex-shrink-0 rounded-xl shadow-md group-hover:scale-105 transition-transform">
            <div x-show="!sidebarCollapsed" x-transition.opacity class="transition-all duration-300">
                <h1 class="font-bold text-lg leading-tight tracking-tight text-white group-hover:text-red-400 transition-colors">LicenseTrack</h1>
                <p class="text-[11px] text-slate-400 truncate max-w-[150px]" title="{{ setting('company_name', config('reminder.company_name', 'PT Hariff Dipa Persada')) }}">{{ setting('company_name', config('reminder.company_name', 'PT Hariff Dipa Persada')) }}</p>
            </div>
        </a>
    </div>

    {{-- Navigation Menu --}}
    <nav class="flex-1 py-6 space-y-1.5 overflow-y-auto transition-all duration-300"
         :class="sidebarCollapsed ? 'px-2' : 'px-4'">
        <p x-show="!sidebarCollapsed" x-transition.opacity class="px-3 text-[10px] font-bold text-slate-500 tracking-widest uppercase mb-3 transition-all duration-300">Menu Utama</p>
        
        @foreach ($items as $item)
            @if ($loop->index === 3)
                <p x-show="!sidebarCollapsed" x-transition.opacity class="px-3 text-[10px] font-bold text-slate-500 tracking-widest uppercase pt-6 mb-3 transition-all duration-300">Konfigurasi & Sistem</p>
                <div x-show="sidebarCollapsed" class="h-px bg-slate-800/80 my-4 mx-3"></div>
            @endif

            <a href="{{ route($item['route']) }}" 
               :title="sidebarCollapsed ? '{{ $item['label'] }}' : ''"
               :class="sidebarCollapsed ? 'justify-center px-2 py-3' : 'px-4 py-3'"
               class="flex items-center gap-3.5 rounded-2xl font-bold text-sm transition-all duration-300 group {{ $item['active'] ? 'bg-white text-slate-900 shadow-lg shadow-white/10' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white font-medium' }}">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0 transition {{ $item['active'] ? 'bg-orange-100 text-orange-600' : 'bg-slate-800/60 group-hover:bg-slate-700/80 text-slate-400 group-hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                    </svg>
                </div>
                <span x-show="!sidebarCollapsed" x-transition.opacity class="transition-all duration-300">{{ $item['label'] }}</span>
                @if (isset($item['badge']) && $item['badge'] !== null)
                    <span x-show="!sidebarCollapsed" x-transition.opacity class="ml-auto text-xs px-2.5 py-0.5 rounded-full font-mono font-bold {{ $item['active'] ? 'bg-slate-900 text-white' : 'bg-slate-800 text-slate-300' }} transition-all duration-300">
                        {{ $item['badge'] }}
                    </span>
                @endif
            </a>
        @endforeach
    </nav>

    {{-- User Info + Logout (Desktop Sidebar) --}}
    <div class="pb-4 transition-all duration-300"
         :class="sidebarCollapsed ? 'px-2' : 'px-4'">
        <div class="flex items-center gap-3 py-3 rounded-2xl bg-slate-800/80 border border-slate-700/60 transition-all duration-300"
             :class="sidebarCollapsed ? 'justify-center px-1' : 'px-3'">
            {{-- Avatar --}}
            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-red-500 to-red-700 text-white font-extrabold flex items-center justify-center text-sm shrink-0 shadow"
                 :title="sidebarCollapsed ? '{{ Auth::user()->name }} ({{ Auth::user()->email }})' : ''">
                {{ substr(Auth::user()->name ?? 'U', 0, 1) }}
            </div>
            {{-- Name --}}
            <div x-show="!sidebarCollapsed" x-transition.opacity class="flex-1 min-w-0 transition-all duration-300">
                <p class="text-xs font-semibold text-white truncate">{{ Auth::user()->name }}</p>
                <p class="text-[10px] text-slate-400 truncate">{{ Auth::user()->email }}</p>
            </div>
            {{-- Logout button --}}
            <form method="POST" action="{{ route('logout') }}" class="shrink-0"
                  :class="sidebarCollapsed ? 'hidden' : 'block'">
                @csrf
                <button type="submit"
                        title="Keluar"
                        class="flex items-center justify-center w-7 h-7 rounded-lg text-slate-400 hover:text-red-400 hover:bg-red-500/10 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                    </svg>
                </button>
            </form>
        </div>
        
        {{-- Collapsed Logout Button --}}
        <div x-show="sidebarCollapsed" class="mt-2 flex justify-center">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        title="Keluar"
                        class="flex items-center justify-center w-8 h-8 rounded-xl text-slate-400 hover:text-red-400 hover:bg-red-500/10 bg-slate-800/60 border border-slate-700/50 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                    </svg>
                </button>
            </form>
        </div>
    </div>
</aside>

{{-- MOBILE SLIDE-OVER SIDEBAR --}}
<div x-show="mobileSidebarOpen" 
     class="fixed inset-0 z-50 flex lg:hidden" 
     role="dialog" 
     aria-modal="true" 
     x-cloak>
    
    {{-- Backdrop --}}
    <div x-show="mobileSidebarOpen" 
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300" 
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0" 
         class="fixed inset-0 bg-slate-900/80 backdrop-blur-xs" 
         @click="mobileSidebarOpen = false"></div>

    {{-- Sidebar Panel --}}
    <div x-show="mobileSidebarOpen" 
         x-transition:enter="transition ease-in-out duration-300 transform"
         x-transition:enter-start="-translate-x-full" 
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in-out duration-300 transform" 
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="-translate-x-full" 
         class="relative flex flex-col w-full max-w-xs bg-[#0B0F19] text-white shadow-2xl pb-4">
        
        {{-- Close Button --}}
        <div class="absolute top-5 right-5">
            <button type="button" @click="mobileSidebarOpen = false" class="p-2 text-slate-400 hover:text-white rounded-lg focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Brand / Logo (Mobile) --}}
        <div class="flex items-center gap-3 px-6 h-20 border-b border-slate-800/80">
            <img src="{{ asset('logo cuma diamond.png') }}" alt="LicenseTrack Logo" class="w-9 h-9 object-contain flex-shrink-0 rounded-xl shadow-md">
            <div>
                <h1 class="font-bold text-lg leading-tight tracking-tight text-white">LicenseTrack</h1>
                <p class="text-[11px] text-slate-400 truncate max-w-[150px]" title="{{ setting('company_name', config('reminder.company_name', 'PT Hariff Dipa Persada')) }}">{{ setting('company_name', config('reminder.company_name', 'PT Hariff Dipa Persada')) }}</p>
            </div>
        </div>

        {{-- Mobile Nav Menu --}}
        <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
            <p class="px-3 text-[10px] font-bold text-slate-500 tracking-widest uppercase mb-3">Menu Utama</p>
            
            @foreach ($items as $item)
                @if ($loop->index === 3)
                    <p class="px-3 text-[10px] font-bold text-slate-500 tracking-widest uppercase pt-6 mb-3">Konfigurasi & Sistem</p>
                @endif

                <a href="{{ route($item['route']) }}" @click="mobileSidebarOpen = false"
                   class="flex items-center gap-3.5 px-4 py-3 rounded-2xl font-bold text-sm transition-all group {{ $item['active'] ? 'bg-white text-slate-900 shadow-lg shadow-white/10' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white font-medium' }}">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0 transition {{ $item['active'] ? 'bg-orange-100 text-orange-600' : 'bg-slate-800/60 group-hover:bg-slate-700/80 text-slate-400 group-hover:text-white' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                        </svg>
                    </div>
                    <span>{{ $item['label'] }}</span>
                    @if (isset($item['badge']) && $item['badge'] !== null)
                        <span class="ml-auto text-xs px-2.5 py-0.5 rounded-full font-mono font-bold {{ $item['active'] ? 'bg-slate-900 text-white' : 'bg-slate-800 text-slate-300' }}">
                            {{ $item['badge'] }}
                        </span>
                    @endif
                </a>
            @endforeach
        </nav>

        {{-- Mobile User Profile & Logout --}}
        <div class="px-4 py-3 mx-4 mb-2 rounded-2xl bg-slate-800/80 border border-slate-700/60 flex items-center justify-between">
            <a href="{{ route('profile.edit') }}" @click="mobileSidebarOpen = false" class="flex items-center gap-3 group min-w-0 flex-1">
                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-red-600 to-red-700 text-white font-extrabold flex items-center justify-center flex-shrink-0 shadow-md text-sm">
                    {{ substr(Auth::user()->name ?? 'U', 0, 1) }}
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-bold text-white group-hover:text-red-400 transition truncate">{{ Auth::user()->name ?? 'Pengguna' }}</p>
                    <p class="text-[10px] text-slate-400 truncate">Profil Saya</p>
                </div>
            </a>
            <form method="POST" action="{{ route('logout') }}" class="flex-shrink-0 ml-2">
                @csrf
                <button type="submit" class="p-2 text-slate-400 hover:text-red-400 hover:bg-slate-700/50 rounded-xl transition" title="Keluar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
        </div>

        {{-- Mobile Bottom Info --}}
        <div class="p-4 m-4 mt-0 rounded-2xl bg-slate-800/60 border border-slate-700/50">
            <div class="flex items-center gap-3">
                <span class="w-2.5 h-2.5 rounded-full {{ $gatewayDot }} {{ $gatewayFilled && $gateway !== 'log' ? 'animate-pulse' : '' }}"></span>
                <div>
                    <p class="text-xs font-semibold text-white">{{ strtoupper($gateway) }} GATEWAY</p>
                    <p class="text-[11px] text-slate-400">Pesan terkirim hari ini: <strong class="text-emerald-400 font-mono">{{ $sentToday }}</strong></p>
                </div>
            </div>
        </div>
    </div>
</div>
