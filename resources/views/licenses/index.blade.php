<x-app-layout>
    <x-slot name="title">Daftar Lisensi</x-slot>
    <x-slot name="header">
        <h1 class="font-display text-xl sm:text-2xl font-semibold text-gray-900 tracking-tight leading-snug">Daftar lisensi</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $licenses->total() }} lisensi terdaftar</p>
    </x-slot>

    <div class="px-4 sm:px-6 lg:px-8 py-6">
        <div class="max-w-none mx-auto space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <a href="{{ route('licenses.index') }}" class="bg-white border border-gray-200/70 rounded-xl shadow-sm p-5 hover:border-red-300 hover:-translate-y-0.5 hover:shadow-md transition-all duration-150 block">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Total</p>
                            <p class="mt-2 text-3xl font-bold tabular-nums {{ $totalLicenses > 0 ? 'text-red-600' : 'text-gray-400' }}">{{ $totalLicenses }}</p>
                            <p class="mt-2 text-xs text-gray-400">Semua lisensi tercatat</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-red-50 text-red-600 flex items-center justify-center flex-shrink-0 shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                    </div>
                </a>
                <a href="{{ route('licenses.index', array_merge(request()->except('page'), ['health' => 'kritis'])) }}" class="bg-white border border-gray-200/70 rounded-xl shadow-sm p-5 hover:border-orange-300 hover:-translate-y-0.5 hover:shadow-md transition-all duration-150 block">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Kritis</p>
                            <p class="mt-2 text-3xl font-bold tabular-nums {{ $criticalCount > 0 ? 'text-orange-600' : 'text-gray-400' }}">{{ $criticalCount }}</p>
                            <p class="mt-2 text-xs text-gray-400">Berakhir dalam 1-30 hari</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center flex-shrink-0 shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                    </div>
                </a>
                <a href="{{ route('licenses.index', array_merge(request()->except('page'), ['health' => 'expired'])) }}" class="bg-white border border-gray-200/70 rounded-xl shadow-sm p-5 hover:border-red-300 hover:-translate-y-0.5 hover:shadow-md transition-all duration-150 block">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Expired</p>
                            <p class="mt-2 text-3xl font-bold tabular-nums {{ $expiredCount > 0 ? 'text-red-600' : 'text-gray-400' }}">{{ $expiredCount }}</p>
                            <p class="mt-2 text-xs text-gray-400">Sudah lewat tanggal berakhir</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-red-50 text-red-600 flex items-center justify-center flex-shrink-0 shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                </a>
            </div>

            <div class="bg-white border border-gray-200/70 rounded-xl shadow-sm p-6">
                <form method="GET" action="{{ route('licenses.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Cari Nama Lisensi</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama..." class="w-full border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500/40 focus:border-red-500 transition">
                    </div>
                    <div class="w-full sm:w-48">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Vendor</label>
                        @php
                            $vendorOptions = ['' => 'Semua Vendor'];
                            foreach($vendors as $v) { $vendorOptions[$v] = $v; }
                        @endphp
                        <x-custom-select 
                            name="vendor" 
                            :options="$vendorOptions" 
                            :selected="request('vendor')" 
                            class="w-full border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500/40 focus:border-red-500 transition px-3 py-2 bg-white"
                        />
                    </div>
                    <div class="w-full sm:w-48">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                        <x-custom-select 
                            name="health" 
                            :options="[
                                '' => 'Semua Status',
                                'aman' => '🟢 Aman',
                                'waspada' => '🟡 Waspada',
                                'kritis' => '🟠 Kritis',
                                'expired' => '🔴 Expired'
                            ]" 
                            :selected="request('health')" 
                            class="w-full border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500/40 focus:border-red-500 transition px-3 py-2 bg-white"
                        />
                    </div>
                    <div class="flex flex-wrap sm:flex-nowrap gap-2 items-end">
                        <button type="submit" class="flex-1 sm:flex-initial justify-center inline-flex items-center gap-2 bg-gradient-to-r from-red-600 to-red-700 text-white px-5 py-2.5 rounded-2xl text-xs font-semibold hover:from-red-700 hover:to-red-800 transition shadow-md shadow-red-500/20 active:scale-[0.98] whitespace-nowrap">Filter</button>
                        @if (request()->hasAny(['search', 'vendor', 'health']))
                            <a href="{{ route('licenses.index') }}" class="flex-1 sm:flex-initial justify-center text-center bg-gray-100 text-gray-600 px-4 py-2 rounded-lg text-sm hover:bg-gray-200 active:scale-[0.98] transition whitespace-nowrap">Reset</a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="bg-white border border-gray-200/70 rounded-xl shadow-sm overflow-hidden">
                @include('licenses._table', ['licenses' => $licenses])
            </div>
        </div>
    </div>
</x-app-layout>
