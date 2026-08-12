<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="font-display text-xl sm:text-2xl font-semibold text-gray-900 tracking-tight leading-snug">Detail lisensi</h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Status Banners --}}
            @if($license->lifecycle_status === 'stopped')
                <div class="bg-slate-100 border border-slate-300 text-slate-700 rounded-2xl p-4 flex items-center gap-3">
                    <div class="w-10 h-10 bg-slate-200 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-sm">Pemantauan Lisensi Dihentikan</p>
                        <p class="text-xs text-slate-500">
                            Dihentikan pada {{ \Carbon\Carbon::parse($license->stopped_at)->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB oleh {{ $license->stoppedBy ? $license->stoppedBy->name : 'System' }}.
                        </p>
                    </div>
                </div>
            @elseif($license->lifecycle_status === 'active' && $license->daysRemaining <= 0)
                <div class="bg-red-50 border border-red-200 text-red-800 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-xs">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-red-600 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-sm text-red-800">Keputusan Diperlukan</p>
                            <p class="text-xs text-red-600">Lisensi ini telah melewati masa berlaku. Silakan lakukan perpanjangan tanggal atau hentikan pemantauan.</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Action Buttons --}}
            <div class="flex flex-wrap justify-end items-center gap-3" x-data="{ showLifecycleModal: false, lifecycleMode: 'renew', startDate: '{{ $license->end_date->copy()->addDay()->format('Y-m-d') }}', endDate: '' }">
                <form method="POST" action="{{ route('licenses.reminders.send-now', $license) }}" onsubmit="confirmSendReminder(event, this)" class="flex-1 sm:flex-initial">
                    @csrf
                    <button type="submit" 
                            @disabled($license->lifecycle_status === 'stopped')
                            class="w-full sm:w-auto justify-center inline-flex items-center gap-2 bg-gradient-to-r from-red-600 to-red-700 text-white px-4 py-2 rounded-2xl text-xs font-semibold hover:from-red-700 hover:to-red-800 transition shadow-sm active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        <span>Kirim reminder sekarang</span>
                    </button>
                </form>

                @if($license->lifecycle_status === 'active')
                    <button type="button" @click="lifecycleMode = 'renew'; showLifecycleModal = true"
                            class="flex-1 sm:flex-initial justify-center inline-flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-2xl text-xs font-semibold hover:bg-indigo-700 transition text-center active:scale-[0.98] shadow-sm">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Perpanjang</span>
                    </button>

                    <form id="stop-license-form" method="POST" action="{{ route('licenses.stop', $license) }}" class="flex-1 sm:flex-initial">
                        @csrf
                        <button type="button" onclick="confirmStopLicense(event)"
                                class="w-full sm:w-auto justify-center inline-flex items-center gap-2 bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-2xl text-xs font-semibold transition active:scale-[0.98] shadow-sm">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                            </svg>
                            <span>Hentikan</span>
                        </button>
                    </form>
                @else
                    <button type="button" @click="lifecycleMode = 'reactivate'; showLifecycleModal = true"
                            class="flex-1 sm:flex-initial justify-center inline-flex items-center gap-2 bg-emerald-600 text-white px-4 py-2 rounded-2xl text-xs font-semibold hover:bg-emerald-700 transition text-center active:scale-[0.98] shadow-sm">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Aktifkan Kembali</span>
                    </button>
                @endif

                <a href="{{ route('licenses.edit', $license) }}"
                   class="flex-1 sm:flex-initial justify-center inline-flex items-center gap-2 bg-white text-slate-700 border border-slate-300 px-4 py-2 rounded-2xl text-xs font-semibold hover:bg-slate-50 transition text-center active:scale-[0.98] shadow-sm">
                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span>Edit</span>
                </a>

                {{-- Delete with confirmation modal --}}
                <div x-data="{ showModal: false }" class="flex-1 sm:flex-initial">
                    <button @click="showModal = true"
                            class="w-full sm:w-auto justify-center inline-flex items-center gap-2 bg-gradient-to-r from-red-600 to-red-700 text-white px-5 py-2.5 rounded-2xl text-xs font-semibold hover:from-red-700 hover:to-red-800 transition shadow-md shadow-red-500/20 active:scale-[0.98]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <span>Hapus</span>
                    </button>

                    {{-- Confirmation Modal --}}
                    <div x-show="showModal" x-cloak
                         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
                         @keydown.escape.window="showModal = false">
                        <div class="bg-white rounded-xl shadow-xl p-6 max-w-sm w-full mx-4" @click.stop>
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-display font-semibold text-gray-800 tracking-tight">Hapus Lisensi?</h3>
                                    <p class="text-sm text-gray-500">Tindakan ini tidak dapat dibatalkan.</p>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 mb-5">
                                Anda akan menghapus <strong>{{ $license->name }}</strong>. Data akan dipindahkan ke tong sampah (soft delete).
                            </p>
                            <div class="flex gap-3 justify-end">
                                <button @click="showModal = false"
                                        class="px-4 py-2 text-sm font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                                    Batal
                                </button>
                                <form method="POST" action="{{ route('licenses.destroy', $license) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                                        Ya, Hapus
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Modal Perpanjang / Aktifkan Kembali --}}
                <div x-show="showLifecycleModal" x-cloak
                     class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 px-4"
                     @click.away="showLifecycleModal = false"
                     @keydown.escape.window="showLifecycleModal = false">
                    <div class="bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full mx-4" @click.stop>
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                                 :class="lifecycleMode === 'renew' ? 'bg-indigo-100 text-indigo-600' : 'bg-emerald-100 text-emerald-600'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-display font-semibold text-gray-800 tracking-tight text-base" 
                                    x-text="lifecycleMode === 'renew' ? 'Perpanjang Lisensi' : 'Aktifkan Kembali' || ''"></h3>
                                <p class="text-xs text-gray-500"
                                   x-text="lifecycleMode === 'renew' ? 'Masukkan periode tanggal perpanjangan lisensi baru.' : 'Aktifkan kembali lisensi yang dihentikan dengan periode baru.' || ''"></p>
                            </div>
                        </div>
                        
                        <form method="POST" :action="lifecycleMode === 'renew' ? '{{ route('licenses.renew', $license) }}' : '{{ route('licenses.reactivate', $license) }}'">
                            @csrf
                            <div class="space-y-4 mb-6 text-left">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tanggal Mulai Baru <span class="text-red-500">*</span></label>
                                    <input type="date" name="start_date" x-model="startDate" required
                                           class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tanggal Berakhir Baru <span class="text-red-500">*</span></label>
                                    <input type="date" name="end_date" x-model="endDate" required
                                           class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>
                            
                            <div class="flex gap-3 justify-end">
                                <button type="button" @click="showLifecycleModal = false"
                                        class="px-4 py-2 text-sm font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                                    Batal
                                </button>
                                <button type="submit"
                                        class="px-4 py-2 text-sm font-medium text-white rounded-lg"
                                        :class="lifecycleMode === 'renew' ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-emerald-600 hover:bg-emerald-700'"
                                        x-text="lifecycleMode === 'renew' ? 'Perpanjang' : 'Aktifkan Kembali' || ''"></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Header Info --}}
            @php
                $health = $license->healthStatus;
                $days   = $license->daysRemaining;
                $badgeClass = match($health) {
                    'aman'    => 'bg-green-100 text-green-700 border-green-200',
                    'waspada' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                    'kritis'  => 'bg-orange-100 text-orange-700 border-orange-200',
                    'expired' => 'bg-red-100 text-red-700 border-red-200',
                };
                $badgeLabel = match($health) {
                    'aman'    => '🟢 Aman',
                    'waspada' => '🟡 Waspada',
                    'kritis'  => '🟠 Kritis',
                    'expired' => '🔴 Expired',
                };
            @endphp

            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                    <div>
                        <h2 class="font-display text-xl font-semibold text-gray-800 tracking-tight">{{ $license->name }}</h2>
                        <p class="text-gray-500 text-sm mt-1">{{ $license->vendor ?? 'Vendor tidak disebutkan' }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 sm:gap-3 flex-shrink-0">
                        @if ($days <= 7 && $days > 0)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 border border-red-200 animate-pulse">
                                <span class="w-2 h-2 rounded-full bg-red-600"></span>
                                Mendesak! (≤7 Hari)
                            </span>
                        @endif
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold border {{ $badgeClass }}">
                            {{ $badgeLabel }}
                        </span>
                        <div class="flex items-baseline gap-1">
                            <span class="text-2xl font-bold {{ $days <= 0 ? 'text-red-600' : ($days <= 7 ? 'text-red-600' : ($days <= 30 ? 'text-orange-600' : ($days <= 90 ? 'text-yellow-600' : 'text-green-600'))) }}">
                                {{ abs($days) }} hari
                            </span>
                            <span class="text-sm text-gray-400">{{ $days >= 0 ? 'tersisa' : 'lalu' }}</span>
                        </div>
                    </div>
                </div>

                @if ($license->description)
                    <p class="text-sm text-gray-600 mt-4 leading-relaxed">{{ $license->description }}</p>
                @endif

                {{-- Detail Grid --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mt-5 pt-5 border-t border-gray-100">
                    <div>
                        <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Mulai Berlaku</p>
                        <p class="text-sm font-semibold text-gray-700 mt-1">{{ $license->start_date->format('d M Y') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Berakhir</p>
                        <p class="text-sm font-semibold text-gray-700 mt-1">{{ $license->end_date->format('d M Y') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Ditambahkan Oleh</p>
                        <p class="text-sm font-semibold text-gray-700 mt-1">{{ $license->creator->name ?? '—' }}</p>
                    </div>
                </div>

                @if ($license->license_key)
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-1">License Key / Nomor Sertifikat</p>
                        <code class="text-sm font-mono bg-gray-100 text-gray-700 px-3 py-1.5 rounded-lg block">
                            {{ $license->license_key }}
                        </code>
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- PIC Contacts --}}
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="font-display text-[15px] font-semibold text-gray-700 tracking-tight mb-4">Kontak yang Harus Diingatkan</h3>
                    <div class="space-y-3">
                        @foreach ($license->contacts as $contact)
                            <div class="flex items-center gap-3 p-3 rounded-lg {{ $contact->is_primary ? 'bg-red-50 border border-red-100' : 'bg-gray-50' }}">
                                <div class="w-9 h-9 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0 text-sm font-bold text-red-600">
                                    {{ strtoupper(substr($contact->name, 0, 1)) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-gray-800 flex items-center gap-1.5">
                                        {{ $contact->name }}
                                        @if ($contact->is_primary)
                                            <span class="text-xs bg-red-600 text-white px-1.5 py-0.5 rounded font-medium">Utama</span>
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-500 font-mono">{{ $contact->phone }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- File Attachments --}}
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="font-display text-[15px] font-semibold text-gray-700 tracking-tight mb-4">Berkas Lampiran Sertifikat / Dokumen Lisensi</h3>
                    @if ($license->files->isEmpty())
                        <p class="text-sm text-gray-400 text-center py-6">Belum ada file lampiran.</p>
                    @else
                        <div class="space-y-2">
                            @foreach ($license->files as $file)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg group">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                        </svg>
                                        <div class="min-w-0">
                                            <p class="text-sm text-gray-700 truncate">{{ $file->original_name }}</p>
                                            <p class="text-xs text-gray-400">{{ round($file->size / 1024, 1) }} KB · {{ $file->mime_type }}</p>
                                        </div>
                                    </div>
                                    <a href="{{ route('licenses.files.download', [$license, $file]) }}"
                                       class="flex-shrink-0 ml-3 text-red-600 hover:text-red-800 transition"
                                       title="Unduh file">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Reminder Timeline --}}
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <h3 class="font-display text-[15px] font-semibold text-gray-700 tracking-tight mb-6">Jadwal Pengingat</h3>
                
                @if (empty($timelineItems))
                    <p class="text-sm text-gray-400 text-center py-4">Belum ada jadwal. Edit lisensi untuk membuat jadwal.</p>
                @else
                    <div class="relative border-l-2 border-gray-100 ml-3 space-y-6">
                        @foreach ($timelineItems as $log)
                            @php
                                $statusConf = match($log->status) {
                                    'sent'    => ['color' => 'text-green-600', 'bg' => 'bg-green-100', 'icon' => '✅', 'label' => 'Terkirim'],
                                    'pending' => ['color' => 'text-gray-500', 'bg' => 'bg-gray-100', 'icon' => '⏳', 'label' => 'Dijadwalkan'],
                                    'queued'  => ['color' => 'text-blue-600', 'bg' => 'bg-blue-100', 'icon' => '🔵', 'label' => 'Dalam antrian'],
                                    'failed'  => ['color' => 'text-red-600', 'bg' => 'bg-red-100', 'icon' => '❌', 'label' => 'Gagal'],
                                    'skipped' => ['color' => 'text-gray-400', 'bg' => 'bg-gray-100', 'icon' => '⏭️', 'label' => 'Dilewati'],
                                };
                                $isPast = $log->scheduled_at->isPast();
                            @endphp
                            
                            <div class="relative pl-6">
                                {{-- Node --}}
                                <div class="absolute -left-2.5 top-1.5 w-5 h-5 rounded-full {{ $statusConf['bg'] }} {{ $statusConf['color'] }} flex items-center justify-center text-xs border-4 border-white shadow-sm">
                                    {{ $statusConf['icon'] }}
                                </div>
                                
                                <div class="{{ $isPast && $log->status !== 'sent' && $log->status !== 'failed' ? 'opacity-60' : '' }}">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-sm font-bold text-gray-800">
                                            {{ $log->label }}
                                        </span>
                                        <span class="text-xs font-medium {{ $statusConf['color'] }} bg-gray-50 px-2 py-0.5 rounded border border-gray-100">
                                            {{ $statusConf['label'] }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 font-medium mb-1">
                                        Jadwal: {{ $log->scheduled_at->translatedFormat('d M Y, H:i') }}
                                    </p>
                                    @if ($log->status === 'sent')
                                        <p class="text-xs text-green-600">Dikirim pada {{ $log->sent_at?->translatedFormat('d M Y, H:i') }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>

    @push('scripts')
    <script>
        function confirmStopLicense(event) {
            event.preventDefault();
            Swal.fire({
                title: "Hentikan Lisensi?",
                text: "Lisensi tidak akan lagi dipantau atau dikirimi pengingat, namun tetap tersimpan di arsip dan dapat diaktifkan kembali.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d97706",
                cancelButtonColor: "#6b7280",
                confirmButtonText: "Ya, Hentikan!",
                cancelButtonText: "Batal",
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: "Memproses...",
                        text: "Mohon tunggu sebentar.",
                        icon: "info",
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    document.getElementById('stop-license-form').submit();
                }
            });
        }

        function confirmSendReminder(event, form) {
            event.preventDefault();
            Swal.fire({
                title: "Kirim Reminder?",
                text: "Kirim pesan pengingat manual ke kontak lisensi ini sekarang?",
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#4f46e5",
                cancelButtonColor: "#6b7280",
                confirmButtonText: "Ya, Kirim!",
                cancelButtonText: "Batal",
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: "Memproses...",
                        text: "Sedang mengirim pesan, mohon tunggu sebentar.",
                        icon: "info",
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    form.submit();
                }
            });
        }
    </script>
    @endpush
</x-app-layout>
