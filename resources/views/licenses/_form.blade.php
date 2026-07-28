{{--
    Shared form partial for license create & edit.
    Expected variables: $license (nullable for create), $action (string), $method (string)
--}}
<div class="space-y-8" x-data="{
    contacts: {{ isset($license) && $license->contacts->count() ? $license->contacts->map(fn($c) => ['name' => $c->name, 'phone' => $c->phone, 'is_primary' => $c->is_primary])->toJson() : '[{name: \'\', phone: \'\', is_primary: true}]' }},
    addContact() {
        this.contacts.push({ name: '', phone: '', is_primary: false });
    },
    removeContact(index) {
        if (this.contacts.length <= 1) return;
        this.contacts.splice(index, 1);
        // Ensure at least one is primary
        if (!this.contacts.some(c => c.is_primary)) {
            this.contacts[0].is_primary = true;
        }
    },
    setPrimary(index) {
        this.contacts.forEach((c, i) => c.is_primary = (i === index));
    },

    // Message Template state
    reminderMode: '{{ old('reminder_mode', isset($license) ? ($license->message_intro && $license->message_closing ? 'custom' : ($license->message_template_id ? 'template' : 'standard')) : 'standard') }}',
    templateId: '{{ old('message_template_id', $license->message_template_id ?? '') }}',
    introText: `{{ old('message_intro', $license->message_intro ?? '') }}`,
    closingText: `{{ old('message_closing', $license->message_closing ?? '') }}`,

    templates: @js($templates->mapWithKeys(fn($t) => [$t->id => ['intro' => $t->intro, 'closing' => $t->closing]])),
    companyName: '{{ setting('company_name', config('reminder.company_name', 'PT Hariff Dipa Persada')) }}',

    get resolvedIntro() {
        let raw = '';
        if (this.reminderMode === 'standard') {
            raw = 'Berikut adalah pengingat dari *{perusahaan}* mengenai lisensi yang berada di bawah tanggung jawab Anda:';
        } else if (this.reminderMode === 'template') {
            let t = this.templates[this.templateId];
            raw = t ? t.intro : '';
        } else {
            raw = this.introText;
        }
        return this.resolvePlaceholders(raw);
    },

    get resolvedClosing() {
        let raw = '';
        if (this.reminderMode === 'standard') {
            raw = 'Mohon segera mengkoordinasikan dan menindaklanjuti proses perpanjangan sebelum tanggal kedaluwarsa agar operasional perusahaan tetap berjalan lancar.';
        } else if (this.reminderMode === 'template') {
            let t = this.templates[this.templateId];
            raw = t ? t.closing : '';
        } else {
            raw = this.closingText;
        }
        return this.resolvePlaceholders(raw);
    },

    resolvePlaceholders(text) {
        if (!text) return '';
        let days = '30';
        let name = document.getElementById('name')?.value || '[Nama Lisensi]';
        let vendor = document.getElementById('vendor')?.value || '[Nama Vendor]';
        let startVal = document.getElementById('start_date')?.value;
        let endVal = document.getElementById('end_date')?.value;

        let startFormat = startVal ? this.formatDate(startVal) : '[Tanggal Mulai]';
        let endFormat = endVal ? this.formatDate(endVal) : '[Tanggal Berakhir]';

        let picName = (this.contacts && this.contacts.length > 0 && this.contacts[0].name) ? this.contacts[0].name : '[Nama PIC]';

        return text
            .replace(/{perusahaan}/g, this.companyName)
            .replace(/{nama_pic}/g, picName)
            .replace(/{nama_lisensi}/g, name)
            .replace(/{vendor}/g, vendor)
            .replace(/{tanggal_mulai}/g, startFormat)
            .replace(/{tanggal_berakhir}/g, endFormat)
            .replace(/{sisa_hari}/g, days);
    },

    formatDate(dateStr) {
        const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
    },

    insertPlaceholder(targetField, placeholder) {
        let textarea = document.getElementById(targetField);
        if (!textarea) return;
        let startPos = textarea.selectionStart;
        let endPos = textarea.selectionEnd;
        let text = targetField === 'message_intro' ? this.introText : this.closingText;

        let newText = text.substring(0, startPos) + placeholder + text.substring(endPos, text.length);

        if (targetField === 'message_intro') {
            this.introText = newText;
        } else {
            this.closingText = newText;
        }

        textarea.focus();
        this.$nextTick(() => {
            textarea.selectionStart = textarea.selectionEnd = startPos + placeholder.length;
        });
    }
}">

    {{-- Section: Informasi Lisensi --}}
    <div class="bg-white rounded-xl border border-gray-200/70 shadow-sm p-6 space-y-5">
        <h2 class="text-base font-semibold text-gray-900 border-b border-gray-100 pb-3">Informasi Lisensi & Sertifikat</h2>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            {{-- Nama Lisensi --}}
            <div class="lg:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Judul <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" id="name"
                       value="{{ old('name', $license->name ?? '') }}"
                       placeholder="Contoh: Sertifikat SSL Domain / Antivirus Kaspersky Endpoint Security"
                       class="w-full border {{ $errors->has('name') ? 'border-red-400' : 'border-gray-300' }} rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-red-500/40 focus:border-red-500 transition">
                @error('name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Vendor --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Vendor / Penerbit</label>
                <input type="text" name="vendor" id="vendor"
                       value="{{ old('vendor', $license->vendor ?? '') }}"
                       placeholder="Contoh: Kaspersky Lab"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-red-500/40 focus:border-red-500 transition">
            </div>

            {{-- Status (Hidden - Selalu Aktif) --}}
            <input type="hidden" name="status" value="active">

            {{-- Tanggal Mulai --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Tanggal Mulai Berlaku <span class="text-red-500">*</span>
                </label>
                <input type="date" name="start_date" id="start_date"
                       value="{{ old('start_date', isset($license) ? $license->start_date->format('Y-m-d') : '') }}"
                       class="w-full border {{ $errors->has('start_date') ? 'border-red-400' : 'border-gray-300' }} rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-red-500/40 focus:border-red-500 transition">
                @error('start_date')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Tanggal Berakhir --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Tanggal Berakhir <span class="text-red-500">*</span>
                </label>
                <input type="date" name="end_date" id="end_date"
                       value="{{ old('end_date', isset($license) ? $license->end_date->format('Y-m-d') : '') }}"
                       class="w-full border {{ $errors->has('end_date') ? 'border-red-400' : 'border-gray-300' }} rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-red-500/40 focus:border-red-500 transition">
                @error('end_date')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Deskripsi --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
            <textarea name="description" id="description" rows="3"
                      placeholder="Deskripsi singkat lisensi ini..."
                      class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-red-500/40 focus:border-red-500 transition resize-none">{{ old('description', $license->description ?? '') }}</textarea>
        </div>

        {{-- License Key --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Input License Key / Sertifikat (Teks / Nomor)
                <span class="text-xs text-gray-400 font-normal ml-1">(disimpan terenkripsi)</span>
            </label>
            <input type="text" name="license_key" id="license_key"
                   value="{{ old('license_key', isset($license) && $license->license_key ? $license->license_key : '') }}"
                   placeholder="Masukkan nomor sertifikat, kode lisensi, atau keterangan kunci (lampiran berkas dapat diunggah di bawah)..."
                   class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm font-mono focus:ring-2 focus:ring-red-500/40 focus:border-red-500 transition">
        </div>
    </div>

    {{-- Section: Kontak PIC --}}
    <div class="bg-white rounded-xl border border-gray-200/70 shadow-sm p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
            <div>
                <h2 class="text-base font-semibold text-gray-900">Kontak yang Harus Diingatkan</h2>
                <p class="text-xs text-gray-400 mt-0.5">Daftar kontak pemilik lisensi/sertifikat yang akan menerima pesan pengingat WhatsApp.</p>
            </div>
            <button type="button" @click="addContact()"
                    class="inline-flex items-center gap-1.5 text-sm text-red-600 hover:text-red-800 font-medium transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Kontak
            </button>
        </div>

        @error('contacts')
            <p class="text-red-500 text-xs">{{ $message }}</p>
        @enderror

        <div class="space-y-3">
            <template x-for="(contact, index) in contacts" :key="index">
                <div class="flex items-start gap-3 p-4 border rounded-lg"
                     :class="contact.is_primary ? 'border-red-200 bg-red-50/30' : 'border-gray-200 bg-gray-50/30'">

                    {{-- Primary indicator --}}
                    <div class="pt-0.5">
                        <button type="button" @click="setPrimary(index)"
                                :title="contact.is_primary ? 'PIC Utama' : 'Jadikan PIC Utama'"
                                :class="contact.is_primary ? 'text-red-500' : 'text-gray-300 hover:text-red-400'"
                                class="transition">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        </button>
                        <input type="hidden" :name="'contacts[' + index + '][is_primary]'" :value="contact.is_primary ? '1' : '0'">
                    </div>

                    {{-- Fields --}}
                    <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Nama PIC <span class="text-red-500">*</span></label>
                            <input type="text"
                                   :name="'contacts[' + index + '][name]'"
                                   x-model="contact.name"
                                   placeholder="Nama lengkap PIC"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500/40 focus:border-red-500 transition">
                            @error('contacts.*.name')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">No. WhatsApp <span class="text-red-500">*</span></label>
                            <input type="text"
                                   :name="'contacts[' + index + '][phone]'"
                                   x-model="contact.phone"
                                   placeholder="Contoh: 081234567890"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500/40 focus:border-red-500 transition">
                            @error('contacts.*.phone')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Remove button --}}
                    <div class="pt-0.5">
                        <button type="button" @click="removeContact(index)"
                                :disabled="contacts.length <= 1"
                                :class="contacts.length <= 1 ? 'text-gray-200 cursor-not-allowed' : 'text-red-400 hover:text-red-600'"
                                class="transition" title="Hapus PIC ini">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        {{-- Validation errors for individual contacts --}}
        @foreach ($errors->keys() as $key)
            @if (str_starts_with($key, 'contacts.') && str_ends_with($key, '.phone'))
                @php $idx = explode('.', $key)[1]; @endphp
                <p class="text-red-500 text-xs">Kontak ke-{{ (int)$idx + 1 }}: {{ $errors->first($key) }}</p>
            @endif
        @endforeach
    </div>

    {{-- Section: Pesan Pengingat --}}
    <div class="bg-white rounded-xl border border-gray-200/70 shadow-sm p-6 space-y-4">
        <div class="border-b border-gray-100 pb-3">
            <h2 class="text-base font-semibold text-gray-900">Pesan Pengingat WhatsApp</h2>
            <p class="text-xs text-gray-400 mt-0.5 font-sans">Atur kalimat pembuka dan penutup untuk pesan pengingat WhatsApp sebelum kedaluwarsa.</p>
        </div>

        <div class="space-y-4">
            {{-- Mode Radios --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- Mode Standard --}}
                <label class="flex items-start gap-3 p-4 border rounded-xl cursor-pointer hover:bg-slate-50 transition"
                       :class="reminderMode === 'standard' ? 'border-red-500 bg-red-55/10' : 'border-gray-200'">
                    <input type="radio" name="reminder_mode" value="standard" x-model="reminderMode" class="mt-1 text-red-600 focus:ring-red-500">
                    <div>
                        <span class="block text-sm font-semibold text-gray-800">Gunakan template standar</span>
                        <span class="block text-xs text-gray-400 mt-0.5">Memakai teks pengingat bawaan sistem.</span>
                    </div>
                </label>

                {{-- Mode Template --}}
                <label class="flex items-start gap-3 p-4 border rounded-xl cursor-pointer hover:bg-slate-50 transition"
                       :class="reminderMode === 'template' ? 'border-red-500 bg-red-55/10' : 'border-gray-200'">
                    <input type="radio" name="reminder_mode" value="template" x-model="reminderMode" class="mt-1 text-red-600 focus:ring-red-500">
                    <div>
                        <span class="block text-sm font-semibold text-gray-800">Pilih template tersimpan</span>
                        <span class="block text-xs text-gray-400 mt-0.5">Gunakan salah satu template yang dikelola di pengaturan.</span>
                    </div>
                </label>

                {{-- Mode Custom --}}
                <label class="flex items-start gap-3 p-4 border rounded-xl cursor-pointer hover:bg-slate-50 transition"
                       :class="reminderMode === 'custom' ? 'border-red-500 bg-red-55/10' : 'border-gray-200'">
                    <input type="radio" name="reminder_mode" value="custom" x-model="reminderMode" class="mt-1 text-red-600 focus:ring-red-500">
                    <div>
                        <span class="block text-sm font-semibold text-gray-800">Tulis khusus untuk lisensi ini</span>
                        <span class="block text-xs text-gray-400 mt-0.5">Tulis kalimat pembuka & penutup unik untuk lisensi ini.</span>
                    </div>
                </label>
            </div>

            {{-- Template Dropdown --}}
            <div x-show="reminderMode === 'template'" class="border border-gray-100 rounded-xl p-4 bg-gray-50/30 space-y-3" x-cloak>
                <label class="block text-sm font-medium text-gray-700">Pilih Template Pesan <span class="text-red-500">*</span></label>
                @php
                    $tmplOptions = ['' => '-- Pilih Template --'];
                    foreach($templates as $tmpl) {
                        $tmplOptions[$tmpl->id] = $tmpl->name . ($tmpl->is_default ? ' (Default)' : '');
                    }
                @endphp
                <x-custom-select 
                    name="message_template_id" 
                    xModel="templateId"
                    :options="$tmplOptions" 
                    class="w-full border {{ $errors->has('message_template_id') ? 'border-red-400' : 'border-gray-300' }} rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500/40 focus:border-red-500 transition bg-white"
                />
                @error('message_template_id')
                    <p class="text-red-500 text-xs">{{ $message }}</p>
                @enderror
            </div>

            {{-- Custom Textareas --}}
            <div x-show="reminderMode === 'custom'" class="border border-gray-100 rounded-xl p-4 bg-gray-50/30 space-y-5" x-cloak>
                <div class="grid grid-cols-1 gap-4">
                    {{-- Intro --}}
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-sm font-medium text-gray-700">Kalimat Pembuka <span class="text-red-500">*</span></label>
                            <span class="text-xs text-gray-400 font-mono" x-text="introText.length + '/500'">0/500</span>
                        </div>
                        <textarea name="message_intro" id="message_intro" x-model="introText" rows="3" maxlength="500"
                                  placeholder="Contoh: Berikut adalah pengingat dari {perusahaan} mengenai lisensi..."
                                  class="w-full border {{ $errors->has('message_intro') ? 'border-red-400' : 'border-gray-300' }} rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500/40 focus:border-red-500 transition resize-none"></textarea>
                        @error('message_intro')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                        <div class="flex flex-wrap items-center gap-1.5 mt-2">
                            <span class="text-xs text-gray-500 mr-1.5 font-medium">Sisipkan:</span>
                            <button type="button" @click="insertPlaceholder('message_intro', '{perusahaan}')" class="px-2 py-1 text-[11px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{perusahaan}</button>
                            <button type="button" @click="insertPlaceholder('message_intro', '{nama_pic}')" class="px-2 py-1 text-[11px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{nama_pic}</button>
                            <button type="button" @click="insertPlaceholder('message_intro', '{nama_lisensi}')" class="px-2 py-1 text-[11px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{nama_lisensi}</button>
                            <button type="button" @click="insertPlaceholder('message_intro', '{vendor}')" class="px-2 py-1 text-[11px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{vendor}</button>
                            <button type="button" @click="insertPlaceholder('message_intro', '{tanggal_mulai}')" class="px-2 py-1 text-[11px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{tanggal_mulai}</button>
                            <button type="button" @click="insertPlaceholder('message_intro', '{tanggal_berakhir}')" class="px-2 py-1 text-[11px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{tanggal_berakhir}</button>
                            <button type="button" @click="insertPlaceholder('message_intro', '{sisa_hari}')" class="px-2 py-1 text-[11px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{sisa_hari}</button>
                        </div>
                    </div>

                    {{-- Closing --}}
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-sm font-medium text-gray-700">Kalimat Penutup <span class="text-red-500">*</span></label>
                            <span class="text-xs text-gray-400 font-mono" x-text="closingText.length + '/500'">0/500</span>
                        </div>
                        <textarea name="message_closing" id="message_closing" x-model="closingText" rows="3" maxlength="500"
                                  placeholder="Contoh: Mohon segera mengkoordinasikan proses perpanjangan..."
                                  class="w-full border {{ $errors->has('message_closing') ? 'border-red-400' : 'border-gray-300' }} rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500/40 focus:border-red-500 transition resize-none"></textarea>
                        @error('message_closing')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                        <div class="flex flex-wrap items-center gap-1.5 mt-2">
                            <span class="text-xs text-gray-500 mr-1.5 font-medium">Sisipkan:</span>
                            <button type="button" @click="insertPlaceholder('message_closing', '{perusahaan}')" class="px-2 py-1 text-[11px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{perusahaan}</button>
                            <button type="button" @click="insertPlaceholder('message_closing', '{nama_pic}')" class="px-2 py-1 text-[11px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{nama_pic}</button>
                            <button type="button" @click="insertPlaceholder('message_closing', '{nama_lisensi}')" class="px-2 py-1 text-[11px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{nama_lisensi}</button>
                            <button type="button" @click="insertPlaceholder('message_closing', '{vendor}')" class="px-2 py-1 text-[11px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{vendor}</button>
                            <button type="button" @click="insertPlaceholder('message_closing', '{tanggal_mulai}')" class="px-2 py-1 text-[11px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{tanggal_mulai}</button>
                            <button type="button" @click="insertPlaceholder('message_closing', '{tanggal_berakhir}')" class="px-2 py-1 text-[11px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{tanggal_berakhir}</button>
                            <button type="button" @click="insertPlaceholder('message_closing', '{sisa_hari}')" class="px-2 py-1 text-[11px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">{sisa_hari}</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Live Preview --}}
            <div class="border border-gray-150 rounded-xl p-5 bg-slate-50 space-y-2.5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider font-sans">Pratinjau Pesan Terkirim (Sebelum Kadaluarsa)</span>
                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-700 font-sans">Live Preview</span>
                </div>

                {{-- WhatsApp Chat Bubble Layout --}}
                <div class="w-full bg-[#E5DDD5] rounded-xl p-4 shadow-inner max-w-lg overflow-hidden border border-slate-300/40 relative">
                    <div class="bg-white rounded-lg p-3 text-sm text-gray-800 shadow-sm relative max-w-[92%] leading-relaxed">
                        <div class="whitespace-pre-wrap font-sans text-xs select-text">Halo *<span x-text="(contacts && contacts.length > 0 && contacts[0].name) ? contacts[0].name : '[Nama PIC]'"></span>*,\n
<span class="font-semibold text-slate-800 bg-slate-100 px-0.5 rounded" x-text="resolvedIntro"></span>\n
📋 *INFORMASI LISENSI*
• *Judul:* <span x-text="document.getElementById('name')?.value || '[Nama Lisensi]'"></span>
• *Vendor / Penyedia:* <span x-text="document.getElementById('vendor')?.value || '[Nama Vendor]'"></span>
• *License Key:* <span x-text="document.getElementById('license_key')?.value || '—'"></span>
• *Masa Berlaku:* <span x-text="document.getElementById('start_date')?.value ? formatDate(document.getElementById('start_date').value) : '[Tanggal Mulai]'"></span> s/d <span x-text="document.getElementById('end_date')?.value ? formatDate(document.getElementById('end_date').value) : '[Tanggal Berakhir]'"></span>
• *Sisa Waktu:* 30 hari lagi
• *Status:* Aktif
• *Deskripsi:* <span x-text="document.getElementById('description')?.value || '—'"></span>\n
<span class="font-semibold text-slate-800 bg-slate-100 px-0.5 rounded" x-text="resolvedClosing"></span>\n
Terima kasih.</div>
                        <div class="text-[10px] text-gray-400 text-right mt-1.5 select-none font-mono">09:00 ✓✓</div>
                    </div>
                </div>
                <p class="text-[11px] text-gray-500 italic mt-1.5 font-sans">
                    * Teks kustom digunakan untuk pengingat sebelum tanggal berakhir. Pesan untuk lisensi yang sudah lewat masa berlaku memakai teks bawaan sistem.
                </p>
            </div>
        </div>
    </div>

    {{-- Section: File Lampiran --}}
    <div class="bg-white rounded-xl border border-gray-200/70 shadow-sm p-6 space-y-4">
        <div class="border-b border-gray-100 pb-3">
            <h2 class="text-base font-semibold text-gray-900">Berkas Lampiran Sertifikat / Dokumen Lisensi (File)</h2>
            <p class="text-xs text-gray-400 mt-0.5">Unggah berkas fisik sertifikat, lisensi, atau dokumen pendukung (PDF, JPG, PNG, DOCX, ZIP — maks. 10 MB per file).</p>
        </div>

        @if (isset($license) && $license->files->count() > 0)
            <div class="space-y-2">
                <p class="text-xs font-medium text-gray-500">File yang sudah ada:</p>
                @foreach ($license->files as $file)
                    <div class="flex items-center justify-between bg-gray-50 rounded-lg px-3 py-2 text-sm">
                        <div class="flex items-center gap-2 min-w-0">
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                            </svg>
                            <span class="truncate text-gray-700">{{ $file->original_name }}</span>
                            <span class="text-gray-400 text-xs flex-shrink-0">({{ round($file->size / 1024, 1) }} KB)</span>
                        </div>
                        <div class="flex items-center gap-2 ml-2">
                            <a href="{{ route('licenses.files.download', [$license, $file]) }}"
                               class="text-red-600 hover:text-red-800 text-xs font-medium">Unduh</a>
                            <form method="POST" action="{{ route('licenses.files.destroy', [$license, $file]) }}"
                                  onsubmit="return confirm('Hapus file ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-medium">Hapus</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ isset($license) && $license->files->count() > 0 ? 'Tambah File Baru' : 'Unggah File' }}
            </label>
            <input type="file" name="files[]" id="files" multiple
                   accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.zip"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-red-500/40 focus:border-red-500 transition file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:font-medium file:bg-red-50 file:text-red-700 hover:file:bg-red-100">
            @error('files.*')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

</div>
