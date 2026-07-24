<x-app-layout>
    <x-slot name="title">Pengaturan</x-slot>
    <x-slot name="header">
        <h1 class="font-display text-xl sm:text-2xl font-semibold text-gray-900 tracking-tight leading-snug">Pengaturan</h1>
        <p class="text-sm text-gray-500 mt-1">Konfigurasi sistem &amp; gateway WhatsApp</p>
    </x-slot>

    @php
        $currentGateway = setting('wa_gateway', config('whatsapp.gateway', 'log'));

        // --- Fonnte Device Warnings ---
        $fonnteWarnings = [];
        if ($currentGateway === 'fonnte' && isset($fonnteDevice) && $fonnteDevice) {
            if (!$fonnteDevice['connected']) {
                $fonnteWarnings[] = ['type' => 'red', 'msg' => 'Device tidak terhubung. Pastikan HP menyala dan WhatsApp tidak logout.'];
            }
            if (isset($fonnteDevice['quota']) && $fonnteDevice['quota'] !== null && $fonnteDevice['quota'] < 100) {
                $fonnteWarnings[] = ['type' => 'yellow', 'msg' => 'Kuota pesan tersisa sedikit (' . $fonnteDevice['quota'] . '). Reminder bisa gagal terkirim.'];
            }
            if (isset($fonnteDevice['expired_at']) && filled($fonnteDevice['expired_at'])) {
                try {
                    $expiredCarbon = \Carbon\Carbon::parse($fonnteDevice['expired_at']);
                    $daysUntilExpiry = (int) now()->diffInDays($expiredCarbon, false); // positive = future
                    if ($daysUntilExpiry >= 0 && $daysUntilExpiry <= 14) {
                        $fonnteWarnings[] = ['type' => 'yellow', 'msg' => 'Paket Fonnte berakhir pada ' . $expiredCarbon->translatedFormat('d F Y') . ' (' . $daysUntilExpiry . ' hari lagi). Perpanjang agar reminder tetap terkirim.'];
                    } elseif ($daysUntilExpiry < 0) {
                        $fonnteWarnings[] = ['type' => 'red', 'msg' => 'Paket Fonnte sudah berakhir pada ' . $expiredCarbon->translatedFormat('d F Y') . '. Reminder tidak akan terkirim.'];
                    }
                } catch (\Exception $e) {}
            }
        }

        // Relative time for "last checked"
        $checkedAtFormatted = null;
        if (isset($checkedAt) && filled($checkedAt)) {
            try {
                $checkedAtFormatted = \Carbon\Carbon::parse($checkedAt)->diffForHumans();
            } catch (\Exception $e) {}
        }

        // Count messages sent today
        $sentToday = \App\Models\WhatsappMessage::where('status', 'sent')
            ->whereDate('created_at', \Carbon\Carbon::today('Asia/Jakarta'))
            ->count();
    @endphp

    <div class="px-4 sm:px-6 lg:px-8 py-6">
        <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">

                {{-- ========================================================== --}}
                {{-- FORM: Pengaturan Umum Reminder                              --}}
                {{-- ========================================================== --}}
                <form method="post" action="{{ route('settings.update') }}" onsubmit="confirmSaveSettings(event, this)" class="bg-white border border-gray-200/70 rounded-xl shadow-sm">
                    @csrf

                    <div class="p-6 border-b border-gray-100">
                        <h2 class="font-display text-[15px] font-semibold text-gray-900 tracking-tight">Pengaturan Reminder</h2>
                        <p class="text-xs text-gray-400 mt-1">Konfigurasi umum untuk jadwal dan gateway pengingat WhatsApp.</p>
                    </div>

                    <div class="divide-y divide-gray-100">
                        <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="reminder_send_time" class="text-sm font-medium text-gray-900">Jam Kirim</label>
                                <p class="text-xs text-gray-400 mt-1">Mengubah jam akan menyesuaikan ulang jadwal pending.</p>
                            </div>
                            <div class="md:col-span-2">
                                <x-text-input id="reminder_send_time" name="reminder_send_time" type="time" class="block w-full max-w-md focus:ring-2 focus:ring-red-500/40 focus:border-red-500 transition" :value="old('reminder_send_time', setting('reminder_send_time', config('reminder.send_time', '09:00')))" required />
                                <x-input-error class="mt-2" :messages="$errors->get('reminder_send_time')" />
                            </div>
                        </div>

                        <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="reminder_company_name" class="text-sm font-medium text-gray-900">Nama Perusahaan</label>
                                <p class="text-xs text-gray-400 mt-1">Dipakai sebagai nama pengirim di isi pesan.</p>
                            </div>
                            <div class="md:col-span-2">
                                <x-text-input id="reminder_company_name" name="reminder_company_name" type="text" class="block w-full max-w-md focus:ring-2 focus:ring-red-500/40 focus:border-red-500 transition" :value="old('reminder_company_name', setting('company_name', config('reminder.company_name', 'PT Hariff Dipa Persada')))" required />
                                <x-input-error class="mt-2" :messages="$errors->get('reminder_company_name')" />
                            </div>
                        </div>

                        <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="wa_gateway" class="text-sm font-medium text-gray-900">Gateway Aktif</label>
                                <p class="text-xs text-gray-400 mt-1">Pilih kanal pengiriman WhatsApp.</p>
                            </div>
                            <div class="md:col-span-2">
                                <select id="wa_gateway" name="wa_gateway" class="block w-full max-w-md border-gray-300 focus:border-red-500 focus:ring-2 focus:ring-red-500/40 rounded-md shadow-sm text-sm transition">
                                    <option value="log" {{ old('wa_gateway', $currentGateway) === 'log' ? 'selected' : '' }}>Log Only (Development)</option>
                                    <option value="meta" {{ old('wa_gateway', $currentGateway) === 'meta' ? 'selected' : '' }}>Meta Cloud API (Live)</option>
                                    <option value="fonnte" {{ old('wa_gateway', $currentGateway) === 'fonnte' ? 'selected' : '' }}>Fonnte (WhatsApp Indonesia)</option>
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('wa_gateway')" />
                            </div>
                        </div>
                    </div>

                    <div class="p-6 bg-gray-50 rounded-b-xl flex justify-end">
                        <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 border border-transparent rounded-lg font-medium text-sm text-white hover:bg-red-700 hover:brightness-110 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all duration-150 shadow-sm">
                            Simpan Pengaturan
                        </button>
                    </div>
                </form>

                {{-- ========================================================== --}}
                {{-- PANEL: Konfigurasi Token Fonnte                              --}}
                {{-- ========================================================== --}}
                <div class="bg-white border border-gray-200/70 rounded-xl shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-gray-100 flex items-center gap-3">
                        <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-green-50 border border-green-200">
                            <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 8.25h3m-3 3h3m-6-3h.007v.008H6.75V9.75zm0 3h.007v.008H6.75v-.008z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="font-display text-[15px] font-semibold text-gray-900 tracking-tight">Token Gateway Fonnte</h2>
                            <p class="text-xs text-gray-400 mt-0.5">Token tersimpan terenkripsi. Tempel token baru untuk mengganti.</p>
                        </div>
                    </div>

                    {{-- Warnings: auto-dismiss after 7s, closeable --}}
                    @foreach ($fonnteWarnings as $warnIdx => $warn)
                        <div
                            x-data="{ show: true }"
                            x-init="setTimeout(() => show = false, 7000)"
                            x-show="show"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-200"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-1"
                            class="mx-6 mt-4 flex items-center justify-between gap-3 px-4 py-3 rounded-lg border text-sm
                                {{ $warn['type'] === 'red' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-amber-50 border-amber-200 text-amber-700' }}"
                        >
                            <div class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                                <span class="font-medium">{{ $warn['msg'] }}</span>
                            </div>
                            <button @click="show = false" type="button"
                                    class="shrink-0 ml-2 rounded p-0.5 hover:bg-black/10 transition"
                                    aria-label="Tutup">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    @endforeach

                    {{-- Token Input Form --}}
                    <form method="post" action="{{ route('settings.fonnte-token') }}" class="p-6 space-y-4">
                        @csrf

                        {{-- Current masked token display --}}
                        @if(isset($maskedFonnteToken) && $maskedFonnteToken)
                            <div>
                                <p class="text-xs font-medium text-gray-500 mb-1">Token Tersimpan</p>
                                <div class="rounded-lg bg-gray-50 border border-gray-200 px-3.5 py-2.5 text-sm font-mono text-gray-700 tracking-widest select-none">
                                    {{ $maskedFonnteToken }}
                                </div>
                                <p class="text-xs text-gray-400 mt-1">Hanya 6 karakter terakhir ditampilkan. Token tidak dapat dibaca seluruhnya dari antarmuka ini.</p>
                            </div>
                        @else
                            <div class="flex items-center gap-2 p-3 rounded-lg bg-orange-50 border border-orange-200">
                                <svg class="w-4 h-4 text-orange-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                </svg>
                                <span class="text-sm text-orange-700 font-medium">Token Fonnte belum dikonfigurasi di database.</span>
                            </div>
                        @endif

                        {{-- New token input --}}
                        <div>
                            <label for="fonnte_token" class="block text-sm font-medium text-gray-700 mb-1.5">
                                Token Baru
                            </label>
                            <input
                                type="password"
                                id="fonnte_token"
                                name="fonnte_token"
                                autocomplete="off"
                                placeholder="Tempel token baru untuk mengganti"
                                class="block w-full max-w-lg px-3.5 py-2.5 border {{ $errors->has('fonnte_token') ? 'border-red-400 ring-2 ring-red-400/30' : 'border-gray-300' }} rounded-lg text-sm focus:ring-2 focus:ring-red-500/40 focus:border-red-500 transition"
                            >
                            @error('fonnte_token')
                                <p class="text-red-500 text-xs mt-1.5 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                                    {{ $message }}
                                </p>
                            @enderror
                            <p class="text-xs text-gray-400 mt-1.5">
                                Mengosongkan field ini tidak akan menghapus token yang ada.
                                Token akan divalidasi ke Fonnte sebelum disimpan.
                            </p>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 border border-transparent rounded-lg font-medium text-sm text-white hover:bg-red-700 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all duration-150 shadow-sm">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                                Simpan &amp; Validasi Token
                            </button>
                        </div>
                    </form>

                    {{-- ---- Device Info Panel ---- --}}
                    @if (isset($fonnteDevice) && $fonnteDevice && $fonnteDevice['valid'])
                        <div class="border-t border-gray-100 p-6">
                            <h3 class="font-display text-[15px] font-semibold text-gray-800 tracking-tight mb-4">Info Device Terdeteksi</h3>
                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3.5 text-sm">
                                {{-- Device Number --}}
                                <div>
                                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Nomor Pengirim</dt>
                                    <dd class="mt-0.5 font-semibold text-gray-800 font-mono">{{ $fonnteDevice['device'] ?? '—' }}</dd>
                                </div>

                                {{-- Connection Status --}}
                                <div>
                                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Status Device</dt>
                                    <dd class="mt-0.5">
                                        @if ($fonnteDevice['connected'])
                                            <span class="inline-flex items-center gap-1.5 text-green-700 font-semibold">
                                                <span class="w-2 h-2 rounded-full bg-green-500 inline-block animate-pulse"></span>
                                                Terhubung
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 text-red-600 font-semibold">
                                                <span class="w-2 h-2 rounded-full bg-red-500 inline-block"></span>
                                                Terputus
                                            </span>
                                        @endif
                                    </dd>
                                </div>

                                {{-- Messages Sent Today --}}
                                <div>
                                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Pesan Terkirim Hari Ini</dt>
                                    <dd class="mt-0.5 font-bold font-mono text-emerald-600">{{ $sentToday }}</dd>
                                </div>

                                {{-- Name --}}
                                <div>
                                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Nama Device</dt>
                                    <dd class="mt-0.5 text-gray-700">{{ $fonnteDevice['name'] ?? '—' }}</dd>
                                </div>

                                {{-- Package --}}
                                <div>
                                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Paket</dt>
                                    <dd class="mt-0.5 text-gray-700">{{ $fonnteDevice['package'] ?? '—' }}</dd>
                                </div>

                                {{-- Quota --}}
                                <div>
                                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Kuota Tersisa</dt>
                                    <dd class="mt-0.5">
                                        @if ($fonnteDevice['quota'] !== null)
                                            <span class="{{ $fonnteDevice['quota'] < 100 ? 'text-orange-600 font-semibold' : 'text-gray-700' }}">
                                                {{ number_format($fonnteDevice['quota']) }}
                                                @if ($fonnteDevice['used'] !== null)
                                                    <span class="text-gray-400 font-normal text-xs">(terpakai {{ number_format($fonnteDevice['used']) }})</span>
                                                @endif
                                            </span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </dd>
                                </div>

                                {{-- Expired --}}
                                <div>
                                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Paket Berakhir</dt>
                                    <dd class="mt-0.5 text-gray-700">
                                        @if (filled($fonnteDevice['expired_at']))
                                            @php
                                                try {
                                                    $expDate = \Carbon\Carbon::parse($fonnteDevice['expired_at']);
                                                    $daysLeft = (int) now()->diffInDays($expDate, false);
                                                    $expFormatted = $expDate->translatedFormat('d F Y');
                                                } catch (\Exception $e) {
                                                    $expFormatted = $fonnteDevice['expired_at'];
                                                    $daysLeft = null;
                                                }
                                            @endphp
                                            <span class="{{ isset($daysLeft) && $daysLeft <= 14 && $daysLeft >= 0 ? 'text-orange-600 font-semibold' : '' }}">
                                                {{ $expFormatted }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </dd>
                                </div>
                            </dl>

                            {{-- Last checked + Re-check button --}}
                            <div class="mt-5 pt-4 border-t border-gray-100 flex flex-wrap items-center justify-between gap-3">
                                <p class="text-xs text-gray-400">
                                    Terakhir dicek:
                                    <span class="text-gray-600">{{ $checkedAtFormatted ?? 'baru saja' }}</span>
                                </p>
                                <form method="post" action="{{ route('settings.fonnte-check') }}">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center gap-1.5 text-xs font-medium text-red-600 hover:text-red-700 border border-red-200 hover:border-red-300 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg transition">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                        </svg>
                                        Cek Ulang
                                    </button>
                                </form>
                            </div>
                        </div>
                    @elseif (isset($fonnteDevice) && $fonnteDevice && !$fonnteDevice['valid'])
                        <div class="border-t border-gray-100 p-6">
                            <div class="flex items-center gap-2 p-3.5 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                </svg>
                                <span>Status device tidak tersedia: {{ $fonnteDevice['error'] ?? 'Tidak ada data.' }}</span>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- ========================================================== --}}
                {{-- PANEL: Template Pesan                                        --}}
                {{-- ========================================================== --}}
                <div class="bg-white border border-gray-200/70 rounded-xl shadow-sm overflow-hidden"
                     x-data="{
                         openAddModal: false,
                         openEditModal: false,
                         editId: null,
                         templateName: '',
                         introText: '',
                         closingText: '',
                         templatesList: @js($templates->mapWithKeys(fn($t) => [$t->id => ['name' => $t->name, 'licenses_count' => $t->licenses_count]])),
                         companyName: '{{ setting('company_name', config('reminder.company_name', 'PT Hariff Dipa Persada')) }}',
                         
                         get resolvedIntro() { return this.resolvePlaceholders(this.introText); },
                         get resolvedClosing() { return this.resolvePlaceholders(this.closingText); },
                         
                         resolvePlaceholders(text) {
                             if (!text) return '';
                             return text
                                 .replace(/{perusahaan}/g, this.companyName)
                                 .replace(/{nama_pic}/g, 'Budi Santoso')
                                 .replace(/{nama_lisensi}/g, 'Antivirus Kaspersky Endpoint')
                                 .replace(/{vendor}/g, 'Kaspersky')
                                 .replace(/{tanggal_mulai}/g, '20 Oktober 2025')
                                 .replace(/{tanggal_berakhir}/g, '20 Oktober 2026')
                                 .replace(/{sisa_hari}/g, '30');
                         },
                         
                         insertPlaceholder(targetField, placeholder) {
                             let text = targetField === 'intro' ? this.introText : this.closingText;
                             let textarea = document.getElementById('modal_' + targetField + (this.editId ? '_edit' : '_add'));
                             if (!textarea) {
                                 if (targetField === 'intro') this.introText += placeholder;
                                 else this.closingText += placeholder;
                                 return;
                             }
                             let startPos = textarea.selectionStart;
                             let endPos = textarea.selectionEnd;
                             let newText = text.substring(0, startPos) + placeholder + text.substring(endPos, text.length);
                             if (targetField === 'intro') {
                                 this.introText = newText;
                             } else {
                                 this.closingText = newText;
                             }
                             textarea.focus();
                             this.$nextTick(() => {
                                 textarea.selectionStart = textarea.selectionEnd = startPos + placeholder.length;
                             });
                         },
                         
                         openEdit(tmpl) {
                             this.editId = tmpl.id;
                             this.templateName = tmpl.name;
                             this.introText = tmpl.intro;
                             this.closingText = tmpl.closing;
                             this.openEditModal = true;
                         },
                         
                         openAdd() {
                             this.editId = null;
                             this.templateName = '';
                             this.introText = '';
                             this.closingText = '';
                             this.openAddModal = true;
                         }
                     }">
                    
                    <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                        <div>
                            <h2 class="font-display text-[15px] font-semibold text-gray-900 tracking-tight">Template Pesan</h2>
                            <p class="text-xs text-gray-400 mt-1 font-sans">Daftar template pesan pengingat WhatsApp kustom.</p>
                        </div>
                        <button type="button" @click="openAdd()"
                                class="inline-flex items-center gap-1.5 text-xs font-semibold bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-lg shadow-sm hover:brightness-110 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Tambah Template
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="bg-gray-50 text-xs text-gray-700 uppercase border-b border-gray-100">
                                <tr>
                                    <th class="px-6 py-3 font-semibold">Nama Template</th>
                                    <th class="px-6 py-3 font-semibold">Kalimat Pembuka</th>
                                    <th class="px-6 py-3 font-semibold">Kalimat Penutup</th>
                                    <th class="px-6 py-3 font-semibold text-center">Penggunaan</th>
                                    <th class="px-6 py-3 font-semibold text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-gray-700">
                                @forelse ($templates as $tmpl)
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="px-6 py-4 font-medium text-gray-900">
                                            <div class="flex items-center gap-2">
                                                <span>{{ $tmpl->name }}</span>
                                                @if ($tmpl->is_default)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-red-50 text-red-700 border border-red-100">Default</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-xs font-sans max-w-xs truncate" title="{{ $tmpl->intro }}">{{ $tmpl->intro }}</td>
                                        <td class="px-6 py-4 text-xs font-sans max-w-xs truncate" title="{{ $tmpl->closing }}">{{ $tmpl->closing }}</td>
                                        <td class="px-6 py-4 text-center text-xs font-medium">{{ $tmpl->licenses_count }} Lisensi</td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="inline-flex items-center justify-center gap-2">
                                                @if (!$tmpl->is_default)
                                                    <form method="POST" action="{{ route('settings.templates.default', $tmpl) }}">
                                                        @csrf
                                                        <button type="submit" class="text-xs text-red-600 hover:text-red-800 font-medium transition">Jadikan Default</button>
                                                    </form>
                                                @endif
                                                <button type="button" @click="openEdit({{ $tmpl->toJson() }})"
                                                        class="text-xs text-gray-600 hover:text-gray-950 font-medium transition">Ubah</button>
                                                
                                                @if (!$tmpl->is_default && $tmpl->licenses_count === 0)
                                                    <form method="POST" action="{{ route('settings.templates.destroy', $tmpl) }}"
                                                          onsubmit="return confirm('Hapus template {{ $tmpl->name }}?')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="text-xs text-red-500 hover:text-red-700 font-medium transition">Hapus</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 text-center text-gray-400 font-sans">Belum ada template terdaftar.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Modal: Tambah Template --}}
                    <div x-show="openAddModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto" x-cloak>
                        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="openAddModal = false"></div>
                        <div class="bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-4xl mx-4 overflow-hidden relative z-10 my-8 flex flex-col max-h-[85vh]">
                            <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                                <h3 class="font-display text-[15px] font-semibold text-gray-900 tracking-tight">Tambah Template Pesan</h3>
                                <button type="button" @click="openAddModal = false" class="text-gray-400 hover:text-gray-500 rounded p-1 hover:bg-slate-50 transition">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            
                            <form method="POST" action="{{ route('settings.templates.store') }}" class="flex flex-col overflow-hidden">
                                @csrf
                                <div class="p-5 overflow-y-auto text-left flex-1">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        {{-- Kolom Kiri: Input Form --}}
                                        <div class="space-y-4">
                                            {{-- Nama --}}
                                            <div>
                                                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Nama Template <span class="text-red-500">*</span></label>
                                                <input type="text" name="name" x-model="templateName" required placeholder="Contoh: Template Software"
                                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500/40 focus:border-red-500 transition">
                                            </div>
                                            
                                            {{-- Intro --}}
                                            <div>
                                                <div class="flex items-center justify-between mb-1">
                                                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">Kalimat Pembuka <span class="text-red-500">*</span></label>
                                                    <span class="text-[10px] text-gray-400 font-mono" x-text="introText.length + '/500'">0/500</span>
                                                </div>
                                                <textarea name="intro" id="modal_intro_add" x-model="introText" rows="2" required maxlength="500"
                                                          placeholder="Contoh: Berikut adalah pengingat dari {perusahaan} mengenai lisensi..."
                                                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500/40 focus:border-red-500 transition resize-none"></textarea>
                                                <div class="flex flex-wrap items-center gap-1.5 mt-2">
                                                    <button type="button" @click="insertPlaceholder('intro', '{perusahaan}')" class="px-2 py-0.5 text-[9px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{perusahaan}</button>
                                                    <button type="button" @click="insertPlaceholder('intro', '{nama_pic}')" class="px-2 py-0.5 text-[9px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{nama_pic}</button>
                                                    <button type="button" @click="insertPlaceholder('intro', '{nama_lisensi}')" class="px-2 py-0.5 text-[9px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{nama_lisensi}</button>
                                                    <button type="button" @click="insertPlaceholder('intro', '{vendor}')" class="px-2 py-0.5 text-[9px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{vendor}</button>
                                                    <button type="button" @click="insertPlaceholder('intro', '{tanggal_mulai}')" class="px-2 py-0.5 text-[9px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{tanggal_mulai}</button>
                                                    <button type="button" @click="insertPlaceholder('intro', '{tanggal_berakhir}')" class="px-2 py-0.5 text-[9px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{tanggal_berakhir}</button>
                                                    <button type="button" @click="insertPlaceholder('intro', '{sisa_hari}')" class="px-2 py-0.5 text-[9px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{sisa_hari}</button>
                                                </div>
                                            </div>
                                            
                                            {{-- Closing --}}
                                            <div>
                                                <div class="flex items-center justify-between mb-1">
                                                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">Kalimat Penutup <span class="text-red-500">*</span></label>
                                                    <span class="text-[10px] text-gray-400 font-mono" x-text="closingText.length + '/500'">0/500</span>
                                                </div>
                                                <textarea name="closing" id="modal_closing_add" x-model="closingText" rows="2" required maxlength="500"
                                                          placeholder="Contoh: Mohon segera mengkoordinasikan proses perpanjangan..."
                                                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500/40 focus:border-red-500 transition resize-none"></textarea>
                                                <div class="flex flex-wrap items-center gap-1.5 mt-2">
                                                    <button type="button" @click="insertPlaceholder('closing', '{perusahaan}')" class="px-2 py-0.5 text-[9px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{perusahaan}</button>
                                                    <button type="button" @click="insertPlaceholder('closing', '{nama_pic}')" class="px-2 py-0.5 text-[9px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{nama_pic}</button>
                                                    <button type="button" @click="insertPlaceholder('closing', '{nama_lisensi}')" class="px-2 py-0.5 text-[9px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{nama_lisensi}</button>
                                                    <button type="button" @click="insertPlaceholder('closing', '{vendor}')" class="px-2 py-0.5 text-[9px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{vendor}</button>
                                                    <button type="button" @click="insertPlaceholder('closing', '{tanggal_mulai}')" class="px-2 py-0.5 text-[9px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{tanggal_mulai}</button>
                                                    <button type="button" @click="insertPlaceholder('closing', '{tanggal_berakhir}')" class="px-2 py-0.5 text-[9px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{tanggal_berakhir}</button>
                                                    <button type="button" @click="insertPlaceholder('closing', '{sisa_hari}')" class="px-2 py-0.5 text-[9px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{sisa_hari}</button>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Kolom Kanan: Pratinjau Chat --}}
                                        <div class="flex flex-col h-full overflow-hidden">
                                            <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Pratinjau Pesan Uji Coba</span>
                                            <div class="flex-1 bg-[#E5DDD5] rounded-xl p-4 border border-slate-300/40 overflow-y-auto flex flex-col justify-start min-h-[300px]">
                                                <div class="bg-white rounded-lg p-3 text-xs text-gray-800 shadow-sm leading-relaxed max-w-[92%]">
                                                    <div class="whitespace-pre-wrap font-sans">Halo *Budi Santoso*,\n
<span class="font-semibold text-slate-800 bg-slate-100 px-0.5 rounded" x-text="resolvedIntro || '(Kalimat pembuka kosong)'"></span>\n
📋 *INFORMASI LISENSI*
• *Judul:* Antivirus Kaspersky Endpoint
• *Vendor / Penyedia:* Kaspersky
• *License Key:* TEST-KEY-KASPERSKY
• *Masa Berlaku:* 20 Oktober 2025 s/d 20 Oktober 2026
• *Sisa Waktu:* 30 hari lagi
• *Status:* Aktif
• *Deskripsi:* Deskripsi contoh lisensi.\n
<span class="font-semibold text-slate-800 bg-slate-100 px-0.5 rounded" x-text="resolvedClosing || '(Kalimat penutup kosong)'"></span>\n
Terima kasih.</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3 shrink-0">
                                    <button type="button" @click="openAddModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition">Batal</button>
                                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition shadow-sm font-sans font-semibold">Simpan</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Modal: Ubah Template --}}
                    <div x-show="openEditModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto" x-cloak>
                        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="openEditModal = false"></div>
                        <div class="bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-4xl mx-4 overflow-hidden relative z-10 my-8 flex flex-col max-h-[85vh]">
                            <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                                <h3 class="font-display text-[15px] font-semibold text-gray-900 tracking-tight">Ubah Template Pesan</h3>
                                <button type="button" @click="openEditModal = false" class="text-gray-400 hover:text-gray-500 rounded p-1 hover:bg-slate-50 transition">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            
                            <form method="POST" :action="'{{ url('settings/templates') }}/' + editId" class="flex flex-col overflow-hidden">
                                @csrf @method('PUT')
                                <div class="p-5 overflow-y-auto text-left flex-1">
                                    
                                    {{-- Alert Warning if template used by licenses --}}
                                    <div x-show="editId && templatesList[editId] && templatesList[editId].licenses_count > 0" 
                                         class="p-3 mb-4 bg-amber-50 border border-amber-200 text-amber-800 text-xs rounded-lg flex items-start gap-2" x-cloak>
                                        <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                        <div>
                                            <strong>Peringatan:</strong> Template ini digunakan oleh <span class="font-bold font-mono" x-text="templatesList[editId].licenses_count"></span> lisensi. Perubahan akan berlaku untuk semua lisensi tersebut.
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        {{-- Kolom Kiri: Input Form --}}
                                        <div class="space-y-4">
                                            {{-- Nama --}}
                                            <div>
                                                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Nama Template <span class="text-red-500">*</span></label>
                                                <input type="text" name="name" x-model="templateName" required placeholder="Contoh: Template Software"
                                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500/40 focus:border-red-500 transition">
                                            </div>
                                            
                                            {{-- Intro --}}
                                            <div>
                                                <div class="flex items-center justify-between mb-1">
                                                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">Kalimat Pembuka <span class="text-red-500">*</span></label>
                                                    <span class="text-[10px] text-gray-400 font-mono" x-text="introText.length + '/500'">0/500</span>
                                                </div>
                                                <textarea name="intro" id="modal_intro_edit" x-model="introText" rows="2" required maxlength="500"
                                                          placeholder="Contoh: Berikut adalah pengingat dari {perusahaan} mengenai lisensi..."
                                                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500/40 focus:border-red-500 transition resize-none"></textarea>
                                                <div class="flex flex-wrap items-center gap-1.5 mt-2">
                                                    <button type="button" @click="insertPlaceholder('intro', '{perusahaan}')" class="px-2 py-0.5 text-[9px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{perusahaan}</button>
                                                    <button type="button" @click="insertPlaceholder('intro', '{nama_pic}')" class="px-2 py-0.5 text-[9px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{nama_pic}</button>
                                                    <button type="button" @click="insertPlaceholder('intro', '{nama_lisensi}')" class="px-2 py-0.5 text-[9px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{nama_lisensi}</button>
                                                    <button type="button" @click="insertPlaceholder('intro', '{vendor}')" class="px-2 py-0.5 text-[9px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{vendor}</button>
                                                    <button type="button" @click="insertPlaceholder('intro', '{tanggal_mulai}')" class="px-2 py-0.5 text-[9px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{tanggal_mulai}</button>
                                                    <button type="button" @click="insertPlaceholder('intro', '{tanggal_berakhir}')" class="px-2 py-0.5 text-[9px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{tanggal_berakhir}</button>
                                                    <button type="button" @click="insertPlaceholder('intro', '{sisa_hari}')" class="px-2 py-0.5 text-[9px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{sisa_hari}</button>
                                                </div>
                                            </div>
                                            
                                            {{-- Closing --}}
                                            <div>
                                                <div class="flex items-center justify-between mb-1">
                                                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">Kalimat Penutup <span class="text-red-500">*</span></label>
                                                    <span class="text-[10px] text-gray-400 font-mono" x-text="closingText.length + '/500'">0/500</span>
                                                </div>
                                                <textarea name="closing" id="modal_closing_edit" x-model="closingText" rows="2" required maxlength="500"
                                                          placeholder="Contoh: Mohon segera mengkoordinasikan proses perpanjangan..."
                                                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500/40 focus:border-red-500 transition resize-none"></textarea>
                                                <div class="flex flex-wrap items-center gap-1.5 mt-2">
                                                    <button type="button" @click="insertPlaceholder('closing', '{perusahaan}')" class="px-2 py-0.5 text-[9px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{perusahaan}</button>
                                                    <button type="button" @click="insertPlaceholder('closing', '{nama_pic}')" class="px-2 py-0.5 text-[9px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{nama_pic}</button>
                                                    <button type="button" @click="insertPlaceholder('closing', '{nama_lisensi}')" class="px-2 py-0.5 text-[9px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{nama_lisensi}</button>
                                                    <button type="button" @click="insertPlaceholder('closing', '{vendor}')" class="px-2 py-0.5 text-[9px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{vendor}</button>
                                                    <button type="button" @click="insertPlaceholder('closing', '{tanggal_mulai}')" class="px-2 py-0.5 text-[9px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{tanggal_mulai}</button>
                                                    <button type="button" @click="insertPlaceholder('closing', '{tanggal_berakhir}')" class="px-2 py-0.5 text-[9px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{tanggal_berakhir}</button>
                                                    <button type="button" @click="insertPlaceholder('closing', '{sisa_hari}')" class="px-2 py-0.5 text-[9px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{sisa_hari}</button>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Kolom Kanan: Pratinjau Chat --}}
                                        <div class="flex flex-col h-full overflow-hidden">
                                            <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Pratinjau Pesan Uji Coba</span>
                                            <div class="flex-1 bg-[#E5DDD5] rounded-xl p-4 border border-slate-300/40 overflow-y-auto flex flex-col justify-start min-h-[300px]">
                                                <div class="bg-white rounded-lg p-3 text-xs text-gray-800 shadow-sm leading-relaxed max-w-[92%]">
                                                    <div class="whitespace-pre-wrap font-sans">Halo *Budi Santoso*,\n
<span class="font-semibold text-slate-800 bg-slate-100 px-0.5 rounded" x-text="resolvedIntro || '(Kalimat pembuka kosong)'"></span>\n
📋 *INFORMASI LISENSI*
• *Judul:* Antivirus Kaspersky Endpoint
• *Vendor / Penyedia:* Kaspersky
• *License Key:* TEST-KEY-KASPERSKY
• *Masa Berlaku:* 20 Oktober 2025 s/d 20 Oktober 2026
• *Sisa Waktu:* 30 hari lagi
• *Status:* Aktif
• *Deskripsi:* Deskripsi contoh lisensi.\n
<span class="font-semibold text-slate-800 bg-slate-100 px-0.5 rounded" x-text="resolvedClosing || '(Kalimat penutup kosong)'"></span>\n
Terima kasih.</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3 shrink-0">
                                    <button type="button" @click="openEditModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition">Batal</button>
                                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition shadow-sm font-sans font-semibold">Simpan Perubahan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- ========================================================== --}}
                {{-- FORM: Kirim Test                                             --}}
                {{-- ========================================================== --}}
                <div class="bg-white border border-gray-200/70 rounded-xl shadow-sm">
                    <div class="p-6 border-b border-gray-100">
                        <h2 class="font-display text-[15px] font-semibold text-gray-900 tracking-tight">Kirim Test</h2>
                        <p class="text-xs text-gray-400 mt-1 font-sans">Uji gateway aktif dengan nomor WhatsApp tujuan.</p>
                    </div>
                    <form method="post" action="{{ route('settings.test') }}" class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                        @csrf
                        <div>
                            <x-input-label for="phone" :value="__('Nomor Tujuan')" />
                            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full max-w-md focus:ring-2 focus:ring-red-500/40 focus:border-red-500 transition" placeholder="628xxx" required />
                            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                        </div>
                        <div>
                            <x-input-label for="template" :value="__('Template')" />
                            <select id="template" name="template" class="mt-1 block w-full max-w-md border-gray-300 focus:border-red-500 focus:ring-2 focus:ring-red-500/40 rounded-md shadow-sm text-sm transition">
                                <option value="reminder">Reminder (Akan Berakhir)</option>
                                <option value="expired">Expired (Sudah Berakhir)</option>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('template')" />
                        </div>
                        <div>
                            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 border border-transparent rounded-lg font-medium text-sm text-white hover:bg-red-700 hover:brightness-110 active:scale-[0.98] transition-all duration-150 shadow-sm font-semibold">
                                Kirim Test
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ============================================================= --}}
            {{-- SIDEBAR                                                        --}}
            {{-- ============================================================= --}}
            <aside class="space-y-6 lg:sticky lg:top-24 self-start">
                {{-- Status Koneksi --}}
                <div class="bg-white border border-gray-200/70 rounded-xl shadow-sm p-6">
                    <h2 class="font-display text-[15px] font-semibold text-gray-900 pb-3 border-b border-gray-100 mb-4 tracking-tight">Status Koneksi</h2>
                    <div class="space-y-4">
                        @php
                            if ($currentGateway === 'fonnte') {
                                $isConnected = isset($fonnteDevice) && $fonnteDevice && $fonnteDevice['valid'] && $fonnteDevice['connected'];
                                $sidebarDot = $isConnected ? 'bg-green-500' : 'bg-red-500';
                                $sidebarLabel = $isConnected ? 'Terhubung' : 'Terputus / Tidak Aktif';
                                $sidebarBadge = $isConnected ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200';
                            } elseif ($currentGateway === 'meta') {
                                $metaFilled = filled(config('whatsapp.access_token'));
                                $sidebarDot = $metaFilled ? 'bg-green-500' : 'bg-red-500';
                                $sidebarLabel = $metaFilled ? 'Token Terisi' : 'Perlu Token';
                                $sidebarBadge = $metaFilled ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200';
                            } else {
                                $isConnected = false;
                                $sidebarDot = 'bg-slate-400';
                                $sidebarLabel = 'Log Mode';
                                $sidebarBadge = 'bg-slate-50 text-slate-600 border-slate-200';
                            }
                        @endphp

                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-sm font-medium text-gray-800">{{ strtoupper($currentGateway) }}</div>
                                <div class="text-xs text-gray-400">Gateway aktif</div>
                            </div>
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium border {{ $sidebarBadge }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $sidebarDot }} {{ $isConnected ?? false ? 'animate-pulse' : '' }}"></span>
                                {{ $sidebarLabel }}
                            </span>
                        </div>

                        @if ($currentGateway === 'fonnte' && isset($maskedFonnteToken) && $maskedFonnteToken)
                            <div>
                                <div class="text-xs font-medium text-gray-500 mb-1">Token (ter-mask)</div>
                                <div class="rounded-lg bg-gray-50 border border-gray-200 px-3 py-2 text-sm font-mono text-gray-700 break-all tracking-widest">{{ $maskedFonnteToken }}</div>
                            </div>
                        @endif

                        @if($currentGateway === 'meta')
                            <div class="text-xs text-gray-500 space-y-1">
                                <div>API: <span class="font-mono text-gray-700">{{ config('whatsapp.api_version', '-') }}</span></div>
                                <div>Template reminder: <span class="font-mono text-gray-700">{{ config('whatsapp.templates.reminder', '-') }}</span></div>
                                <div>Template expired: <span class="font-mono text-gray-700">{{ config('whatsapp.templates.expired', '-') }}</span></div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="bg-white border border-gray-200/70 rounded-xl shadow-sm p-6">
                    <h2 class="font-display text-[15px] font-semibold text-gray-900 pb-3 border-b border-gray-100 mb-3 tracking-tight">Catatan Penting</h2>
                    <p class="text-sm text-gray-600 leading-relaxed">
                        Untuk Fonnte, HP yang terhubung harus tetap menyala dan WhatsApp tidak logout agar pengiriman reminder berjalan lancar.
                    </p>
                    <p class="mt-3 text-xs text-gray-400 leading-relaxed">
                        Token Fonnte tersimpan terenkripsi di database, bukan di <span class="font-mono">.env</span>.
                        Setiap penggantian token tercatat di audit log.
                    </p>
                    <p class="mt-3 text-xs text-gray-400">
                        Mode Log hanya mencatat pesan untuk development dan tidak mengirim WhatsApp sungguhan.
                    </p>
                </div>
            </aside>
        </div>
    </div>

    @push('scripts')
    <script>
        function confirmSaveSettings(event, form) {
            // Only confirm if gateway changed to a different value
        }
    </script>
    @endpush
</x-app-layout>
