<x-app-layout>
    <x-slot name="title">Pengaturan</x-slot>
    <x-slot name="header">
        <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">Pengaturan</h1>
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
                        <h2 class="text-base font-semibold text-gray-900">Pengaturan Reminder</h2>
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
                                <x-text-input id="reminder_company_name" name="reminder_company_name" type="text" class="block w-full max-w-md focus:ring-2 focus:ring-red-500/40 focus:border-red-500 transition" :value="old('reminder_company_name', setting('reminder_company_name', config('reminder.company_name', 'PT Hariff')))" required />
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
                            <h2 class="text-base font-semibold text-gray-900">Token Gateway Fonnte</h2>
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
                            <h3 class="text-sm font-semibold text-gray-800 mb-4">Info Device Terdeteksi</h3>
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
                {{-- FORM: Kirim Test                                             --}}
                {{-- ========================================================== --}}
                <div class="bg-white border border-gray-200/70 rounded-xl shadow-sm">
                    <div class="p-6 border-b border-gray-100">
                        <h2 class="text-base font-semibold text-gray-900">Kirim Test</h2>
                        <p class="text-xs text-gray-400 mt-1">Uji gateway aktif dengan nomor WhatsApp tujuan.</p>
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
                            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 border border-transparent rounded-lg font-medium text-sm text-white hover:bg-red-700 hover:brightness-110 active:scale-[0.98] transition-all duration-150 shadow-sm">
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
                    <h2 class="text-base font-semibold text-gray-900 pb-3 border-b border-gray-100 mb-4">Status Koneksi</h2>
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
                    <h2 class="text-base font-semibold text-gray-900 pb-3 border-b border-gray-100 mb-3">Catatan Penting</h2>
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
