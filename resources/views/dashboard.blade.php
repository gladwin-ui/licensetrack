@php
    $activeLicensesCount = \App\Models\License::whereIn('status', ['active', 'renewed'])->count();
    $attentionThisWeekCount = \App\Models\License::where('status', 'active')
        ->where('end_date', '<=', \Carbon\Carbon::today('Asia/Jakarta')->addDays(7))
        ->count();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="font-display text-xl sm:text-2xl font-semibold text-gray-900 tracking-tight leading-snug">Dashboard</h1>
            <p class="text-sm text-gray-500 mt-1">
                {{ $activeLicensesCount }} lisensi aktif · {{ $attentionThisWeekCount }} butuh perhatian minggu ini
            </p>
        </div>
    </x-slot>

    <div class="space-y-6 -mt-2 sm:-mt-4">
        {{-- Flash Messages --}}
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" x-transition.duration.500ms class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl px-5 py-3.5 flex items-center gap-3 shadow-xs">
                <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span class="font-semibold text-sm">{{ session('success') }}</span>
            </div>
        @endif

        {{-- Action Buttons --}}
        <div class="flex justify-end gap-3">
            <form method="POST" action="{{ route('reminders.dispatch-now') }}" class="flex-1 sm:flex-initial">
                @csrf
                <button type="submit" class="w-full sm:w-auto justify-center inline-flex items-center gap-2 bg-white text-slate-700 border border-slate-300 px-4 py-2.5 rounded-2xl text-xs font-semibold hover:bg-slate-50 shadow-sm active:scale-[0.98] transition">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Proses antrian</span>
                </button>
            </form>

            <a href="{{ route('licenses.create') }}"
               class="flex-1 sm:flex-initial justify-center inline-flex items-center gap-2 bg-gradient-to-r from-red-600 to-red-700 text-white px-5 py-2.5 rounded-2xl text-xs font-semibold hover:from-red-700 hover:to-red-800 transition shadow-md shadow-red-500/20 text-center active:scale-[0.98]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Tambah lisensi</span>
            </a>
        </div>

        {{-- 4 KPI CARDS (Diadaptasi dari Referensi Desain) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            {{-- Card 1: Total Lisensi --}}
            <a href="{{ route('dashboard') }}" class="bg-white rounded-xl border border-gray-200/70 p-5 shadow-sm hover:shadow-md hover:scale-[1.01] transition duration-200 group block relative">
                <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center group-hover:scale-105 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <p class="text-3xl font-semibold text-gray-900 mt-3 tabular-nums">{{ $totalLicenses }}</p>
                <p class="text-sm font-semibold text-gray-500 mt-1">Total lisensi</p>
                <p class="text-xs text-gray-400 mt-1">Semua data lisensi terdaftar</p>
            </a>

            {{-- Card 2: Waspada (31–90 Hari) --}}
            <a href="{{ route('dashboard', ['health' => 'waspada']) }}" class="bg-white rounded-xl border {{ request('health') === 'waspada' ? 'border-amber-400 ring-1 ring-amber-400/30 shadow-md' : 'border-gray-200/70 shadow-sm' }} p-5 hover:shadow-md hover:scale-[1.01] transition duration-200 group block relative">
                <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center group-hover:scale-105 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-3xl font-semibold text-amber-700 mt-3 tabular-nums">{{ $warningCount }}</p>
                <p class="text-sm font-semibold text-gray-500 mt-1">Waspada (31–90 hari)</p>
                <p class="text-xs text-gray-400 mt-1">Perlu persiapan dokumen</p>
            </a>

            {{-- Card 3: Kritis (1–30 Hari) --}}
            <a href="{{ route('dashboard', ['health' => 'kritis']) }}" class="bg-white rounded-xl border {{ request('health') === 'kritis' ? 'border-red-400 ring-1 ring-red-400/30 shadow-md' : 'border-gray-200/70 shadow-sm' }} p-5 hover:shadow-md hover:scale-[1.01] transition duration-200 group block relative">

                <div class="w-10 h-10 rounded-lg bg-red-50 text-red-600 flex items-center justify-center group-hover:scale-105 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <p class="text-3xl font-semibold text-red-700 mt-3 tabular-nums">{{ $expiringIn30 }}</p>
                <p class="text-sm font-semibold text-gray-500 mt-1">Kritis (1–30 hari)</p>
                <p class="text-xs text-gray-400 mt-1">Segera butuh perpanjangan!</p>
            </a>

            {{-- Card 4: Expired --}}
            <a href="{{ route('dashboard', ['health' => 'expired']) }}" class="bg-white rounded-xl border {{ request('health') === 'expired' ? 'border-slate-400 ring-1 ring-slate-400/30 shadow-md' : 'border-gray-200/70 shadow-sm' }} p-5 hover:shadow-md hover:scale-[1.01] transition duration-200 group block relative">
                <div class="w-10 h-10 rounded-lg bg-gray-100 text-gray-500 flex items-center justify-center group-hover:scale-105 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
                <p class="text-3xl font-semibold text-gray-500 mt-3 tabular-nums">{{ $expiredCount }}</p>
                <p class="text-sm font-semibold text-gray-500 mt-1">Expired</p>
                <p class="text-xs text-gray-400 mt-1">Segera lakukan tindakan!</p>
            </a>
        </div>

        {{-- Filter & Search Form --}}
        <div class="bg-white rounded-3xl p-6 shadow-xs border border-slate-200/70">
            <form method="GET" action="{{ route('dashboard') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                {{-- Search by name --}}
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Cari Nama Lisensi</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Kata kunci..."
                           class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-3.5 py-2.5 text-sm focus:bg-white focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition">
                </div>

                {{-- Filter vendor --}}
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Vendor / Instansi</label>
                    @php
                        $vendorOptions = ['' => 'Semua Vendor'];
                        foreach($vendors as $v) { $vendorOptions[$v] = $v; }
                    @endphp
                    <x-custom-select 
                        name="vendor" 
                        :options="$vendorOptions" 
                        :selected="request('vendor')" 
                    />
                </div>

                {{-- Filter status health --}}
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Status Kesehatan</label>
                    <x-custom-select 
                        name="health" 
                        :options="[
                            '' => 'Semua Status',
                            'aman' => '🟢 Aman (>90 hari)',
                            'waspada' => '🟡 Waspada (31–90 hari)',
                            'kritis' => '🟠 Kritis (1–30 hari)',
                            'expired' => '🔴 Expired (<=0 hari)'
                        ]" 
                        :selected="request('health')" 
                    />
                </div>

                {{-- Filter status lisensi + Tombol --}}
                <div class="flex flex-col sm:flex-row gap-2 items-stretch sm:items-end">
                    <div class="flex-1">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Status Aktif</label>
                        <x-custom-select 
                            name="status" 
                            :options="[
                                '' => 'Semua',
                                'active' => 'Aktif',
                                'renewed' => 'Diperpanjang',
                                'cancelled' => 'Dibatalkan'
                            ]" 
                            :selected="request('status')" 
                        />
                    </div>
                    <div class="flex items-end gap-2 mt-2 sm:mt-0">
                        <button type="submit" class="flex-1 sm:flex-initial justify-center bg-red-600 hover:bg-red-700 text-white font-bold px-5 py-2.5 rounded-2xl text-sm transition shadow-sm active:scale-[0.98] whitespace-nowrap">
                            Filter
                        </button>
                        @if (request()->hasAny(['search', 'vendor', 'health', 'status']))
                            <a href="{{ route('dashboard') }}" class="flex-1 sm:flex-initial justify-center text-center bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold px-4 py-2.5 rounded-2xl text-sm transition whitespace-nowrap">
                                Reset
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        {{-- MIDDLE & BOTTOM GRID SECTION (Tabel Kiri + Donut Chart & Reminder Kanan) --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            {{-- LEFT 2 COLUMNS: Tabel Lisensi Utama / Mendesak --}}
            <div class="lg:col-span-2 bg-white rounded-3xl p-6 sm:p-8 shadow-xs border border-slate-200/70 flex flex-col">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-6 border-b border-slate-100 mb-6 gap-4">
                    <div>
                        <h3 class="font-display text-[15px] font-semibold text-slate-900 tracking-tight">Lisensi mendesak</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Diurutkan berdasarkan tanggal jatuh tempo terdekat</p>
                    </div>
                    @if (request()->hasAny(['search', 'vendor', 'health', 'status']))
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-red-50 text-red-700">
                            Terfilter ({{ $licenses->total() }} data)
                        </span>
                    @endif
                </div>

                @if ($licenses->isEmpty())
                    <div class="py-16 text-center px-4 flex-1 flex flex-col items-center justify-center">
                        <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <h3 class="font-display text-[15px] font-semibold text-slate-900 tracking-tight">Belum ada lisensi terdaftar</h3>
                        <p class="text-xs text-slate-400 mt-1 max-w-xs mx-auto">Tambahkan lisensi pertama untuk mulai memantau masa berlakunya.</p>
                        <a href="{{ route('licenses.create') }}" class="mt-4 inline-flex items-center gap-2 bg-red-600 text-white font-semibold px-4 py-2 rounded-xl text-xs hover:bg-red-700 transition active:scale-[0.98]">
                            <span>+ Tambah lisensi</span>
                        </a>
                    </div>
                @else
                    <div class="overflow-x-auto flex-1">
                        <table class="w-full min-w-[720px] text-left border-collapse text-sm">
                            <thead>
                                <tr class="border-b border-slate-100 text-slate-400 font-bold text-xs uppercase tracking-wider">
                                    <th class="pb-3 pr-4">Nama Lisensi & Vendor</th>
                                    <th class="pb-3 px-4">PIC Utama</th>
                                    <th class="pb-3 px-4">Tgl. Berakhir</th>
                                    <th class="pb-3 px-4 text-center">Status</th>
                                    <th class="pb-3 pl-4 text-center">Sisa Hari</th>
                                    <th class="pb-3 pl-4 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($licenses as $license)
                                    @php
                                        $primaryContact = $license->contacts->firstWhere('is_primary', true) ?? $license->contacts->first();
                                        $days = $license->daysRemaining;
                                        $health = $license->healthStatus;
                                        $totalDays = max(1, $license->start_date->diffInDays($license->end_date));
                                        $remaining = max(0, min($totalDays, $days));
                                        $progress = (int) round(($remaining / $totalDays) * 100);
                                        $badgeClass = match($health) {
                                            'aman'    => 'text-emerald-600 font-bold',
                                            'waspada' => 'text-yellow-600 font-bold',
                                            'kritis'  => 'text-orange-600 font-bold',
                                            'expired' => 'text-red-600 font-bold',
                                        };
                                        $badgeLabel = match($health) {
                                            'aman'    => 'Aman',
                                            'waspada' => 'Waspada',
                                            'kritis'  => 'Kritis',
                                            'expired' => 'Expired',
                                        };
                                    @endphp
                                    <tr class="hover:bg-gray-50 transition-colors group">
                                        {{-- Nama & Vendor --}}
                                        <td class="py-4 pr-4">
                                            <a href="{{ route('licenses.show', $license) }}" class="font-bold text-slate-900 group-hover:text-red-600 transition block leading-snug">
                                                {{ $license->name }}
                                            </a>
                                            <p class="text-xs text-slate-400 mt-0.5 flex items-center gap-1.5">
                                                <span>{{ $license->vendor ?? 'Internal / Non-Vendor' }}</span>
                                                @if ($license->status !== 'active')
                                                    <span class="px-1.5 py-0.2 rounded text-[10px] bg-slate-100 text-slate-600 font-semibold">{{ ucfirst($license->status) }}</span>
                                                @endif
                                            </p>
                                        </td>

                                        {{-- PIC Utama --}}
                                        <td class="py-4 px-4">
                                            @if ($primaryContact)
                                                <div class="flex items-center gap-2.5">
                                                    <div class="w-7 h-7 rounded-full bg-red-100 text-red-700 font-bold text-xs flex items-center justify-center flex-shrink-0">
                                                        {{ substr($primaryContact->name, 0, 1) }}
                                                    </div>
                                                    <div>
                                                        <p class="font-semibold text-xs text-slate-800 leading-tight">{{ $primaryContact->name }}</p>
                                                        <p class="text-[11px] font-mono text-slate-400 mt-0.5">{{ $primaryContact->phone }}</p>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-xs text-slate-400 font-medium">—</span>
                                            @endif
                                        </td>

                                        {{-- Tgl Berakhir --}}
                                        <td class="py-4 px-4 text-slate-600 font-mono text-xs font-semibold">
                                            {{ $license->end_date->format('d/m/Y') }}
                                        </td>

                                        {{-- Status Health --}}
                                        <td class="py-4 px-4 text-center">
                                            <span class="text-xs {{ $badgeClass }}">
                                                {{ $badgeLabel }}
                                            </span>
                                        </td>

                                        {{-- Sisa Hari --}}
                                        <td class="py-4 pl-4 text-center">
                                            <div class="flex flex-col items-center justify-center gap-1 max-w-[130px] mx-auto">
                                                <div>
                                                    <span class="font-bold text-xs {{ $days <= 0 ? 'text-red-600' : ($days <= 7 ? 'text-red-600' : ($days <= 30 ? 'text-orange-600' : ($days <= 90 ? 'text-yellow-600' : 'text-emerald-600'))) }}">
                                                        {{ abs($days) }}
                                                        <span class="text-xs font-normal text-slate-400">{{ $days >= 0 ? 'hari lagi' : 'hari lalu' }}</span>
                                                    </span>
                                                </div>
                                                <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden mt-0.5">
                                                    <div class="h-full rounded-full transition-all duration-300 {{ $days <= 0 ? 'bg-red-500 w-full' : ($days <= 7 ? 'bg-red-600 animate-pulse' : ($days <= 30 ? 'bg-orange-500' : ($days <= 90 ? 'bg-yellow-500' : 'bg-emerald-500'))) }}"
                                                         style="width: {{ $days <= 0 ? 100 : $progress }}%"></div>
                                                </div>
                                            </div>
                                        </td>

                                        {{-- Aksi --}}
                                        <td class="py-4 pl-4 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <a href="{{ route('licenses.show', $license) }}" title="Lihat Detail"
                                                   class="p-1.5 rounded-lg text-red-600 hover:bg-red-50 transition">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                    </svg>
                                                </a>
                                                <a href="{{ route('licenses.edit', $license) }}" title="Edit Lisensi"
                                                   class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100 transition">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                    </svg>
                                                </a>
                                                <form method="POST" action="{{ route('licenses.destroy', $license) }}" onsubmit="confirmDeleteLicense(event, this)" class="inline">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" title="Hapus Lisensi" class="p-1.5 rounded-lg text-red-500 hover:bg-red-50 transition">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    @if ($licenses->hasPages())
                        <div class="mt-6 pt-4 border-t border-slate-100">
                            {{ $licenses->links() }}
                        </div>
                    @endif
                @endif
            </div>

            {{-- RIGHT 1 COLUMN: Donut Chart & Upcoming Reminder --}}
            <div class="space-y-8 flex flex-col self-start w-full">
                
                {{-- Donut Chart Card --}}
                <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-xs border border-slate-200/70 flex flex-col items-center">
                    <div class="w-full flex items-center justify-between pb-4 border-b border-slate-100 mb-6">
                        <div>
                            <h3 class="font-display text-[15px] font-semibold text-slate-900 tracking-tight">Distribusi Status</h3>
                        </div>
                        <span class="text-xs font-semibold text-slate-500 bg-slate-100 px-2.5 py-1 rounded-full">{{ $totalLicenses }} Total</span>
                    </div>

                    @if ($totalLicenses == 0)
                        <div class="flex-1 flex items-center justify-center py-12">
                            <p class="text-sm text-slate-400 text-center">Belum ada data</p>
                        </div>
                    @else
                        {{-- Canvas Container with Center Text --}}
                        <div class="relative w-28 h-28 flex items-center justify-center">
                            <canvas id="dashboardStatusChart"></canvas>
                            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                <span class="text-2xl font-bold text-slate-900">{{ $totalLicenses }}</span>
                                <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Total</span>
                            </div>
                        </div>

                        {{-- Legend --}}
                        <div class="w-full grid grid-cols-2 gap-3 mt-6 pt-6 border-t border-slate-100 text-xs">
                            <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50">
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                                    <span class="font-semibold text-slate-700">Aman</span>
                                </div>
                                <strong class="font-mono text-slate-900">{{ $statusDistribution['aman'] ?? 0 }}</strong>
                            </div>
                            <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50">
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
                                    <span class="font-semibold text-slate-700">Waspada</span>
                                </div>
                                <strong class="font-mono text-slate-900">{{ $statusDistribution['waspada'] ?? 0 }}</strong>
                            </div>
                            <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50">
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full bg-orange-500"></span>
                                    <span class="font-semibold text-slate-700">Kritis</span>
                                </div>
                                <strong class="font-mono text-slate-900">{{ $statusDistribution['kritis'] ?? 0 }}</strong>
                            </div>
                            <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50">
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full bg-red-600"></span>
                                    <span class="font-semibold text-slate-700">Expired</span>
                                </div>
                                <strong class="font-mono text-slate-900">{{ $statusDistribution['expired'] ?? 0 }}</strong>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Mini Reminder Widget --}}
                <div class="bg-gradient-to-br from-slate-950 via-[#111625] to-[#241318] text-white rounded-3xl p-6 sm:p-8 shadow-xl border border-slate-800/80 relative overflow-hidden flex-1 flex flex-col justify-between">
                    <div class="absolute -right-10 -top-10 w-40 h-40 bg-red-500/15 rounded-full blur-2xl pointer-events-none"></div>
                    
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-4">
                            <span class="px-3 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-wider bg-red-500/20 text-red-300 border border-red-500/30">Jadwal Reminder</span>
                            <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h4 class="font-display text-[15px] font-semibold text-white tracking-tight leading-snug">Pengingat WhatsApp Berikutnya</h4>
                        
                        @if (isset($upcomingReminders) && $upcomingReminders->isNotEmpty())
                            @php
                                $nextReminder = $upcomingReminders->first();
                            @endphp
                            <p class="text-sm text-slate-300 mt-3 leading-relaxed">
                                Jadwal reminder <strong class="text-white font-bold">{{ $nextReminder->type === 'h_7' ? 'H-7' : ($nextReminder->type === 'h_30' ? 'H-30' : strtoupper($nextReminder->type)) }}</strong> untuk lisensi <strong class="text-white font-semibold">{{ $nextReminder->license->name ?? 'Lisensi' }}</strong> akan dikirim pada <strong class="text-red-400 font-mono font-semibold">{{ \Carbon\Carbon::parse($nextReminder->scheduled_at)->format('d/m/Y H:i') }} WIB</strong>.
                            </p>
                        @else
                            <p class="text-sm text-slate-300 mt-3 leading-relaxed">
                                Belum ada antrian pengiriman pesan WhatsApp otomatis dalam waktu dekat. Semua lisensi dalam kondisi terjadwal normal.
                            </p>
                        @endif
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-800 flex items-center justify-between relative z-10">
                        <div class="flex -space-x-2">
                            <div class="w-7 h-7 rounded-full bg-red-600 border-2 border-slate-900 flex items-center justify-center text-[10px] font-bold text-white shadow-sm" title="WhatsApp Gateway">WA</div>
                            <div class="w-7 h-7 rounded-full bg-emerald-600 border-2 border-slate-900 flex items-center justify-center text-[10px] font-bold text-white shadow-sm" title="Automated Scheduler">⚡</div>
                        </div>
                        <a href="{{ route('reminders.index') }}" class="text-xs font-bold text-red-400 hover:text-red-300 transition inline-flex items-center gap-1">Lihat Riwayat Reminder &rarr;</a>
                    </div>
                </div>

            </div>

        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const canvas = document.getElementById('dashboardStatusChart');
                if (!canvas) return;
                
                const ctx = canvas.getContext('2d');
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Aman (>90 Hari)', 'Waspada (31-90 Hari)', 'Kritis (1-30 Hari)', 'Expired (<=0 Hari)'],
                        datasets: [{
                            data: [
                                {{ $statusDistribution['aman'] ?? 0 }},
                                {{ $statusDistribution['waspada'] ?? 0 }},
                                {{ $statusDistribution['kritis'] ?? 0 }},
                                {{ $statusDistribution['expired'] ?? 0 }}
                            ],
                            backgroundColor: [
                                '#10B981', // Emerald Aman
                                '#EAB308', // Yellow Waspada
                                '#F97316', // Orange Kritis
                                '#DC2626'  // Red Expired
                            ],
                            borderWidth: 0,
                            hoverOffset: 6
                        }]
                    },
                    options: {
                        cutout: '76%',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: '#0B0F19',
                                titleFont: { family: 'Figtree', weight: 'bold' },
                                bodyFont: { family: 'Figtree' },
                                padding: 12,
                                cornerRadius: 12
                            }
                        }
                    }
                });
            });
        </script>
    @endpush
</x-app-layout>
