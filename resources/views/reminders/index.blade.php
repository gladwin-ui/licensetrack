<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-xl sm:text-2xl font-semibold text-gray-800 tracking-tight leading-snug">Riwayat Pengingat WhatsApp</h1>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 flex items-start gap-2 whitespace-pre-wrap">
                    <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <div>{{ session('success') }}</div>
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3 flex items-start gap-2 whitespace-pre-wrap">
                    <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <div>{{ session('error') }}</div>
                </div>
            @endif

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <form method="GET" action="{{ route('reminders.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                        <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-red-500 focus:border-red-500">
                            <option value="">Semua Status</option>
                            <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Terkirim</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Tertunda / Proses</option>
                            <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Gagal</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Cari Lisensi / PIC</label>
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Nama lisensi atau PIC..."
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-red-500 focus:border-red-500">
                    </div>
                    <div class="flex flex-wrap sm:flex-nowrap gap-2 items-end">
                        <button type="submit" class="flex-1 sm:flex-initial justify-center bg-red-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-700 transition whitespace-nowrap">Filter</button>
                        @if(request()->hasAny(['status', 'search']))
                            <a href="{{ route('reminders.index') }}" class="flex-1 sm:flex-initial justify-center text-center bg-gray-100 text-gray-600 px-4 py-2 rounded-lg text-sm hover:bg-gray-200 transition whitespace-nowrap">Reset</a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[750px] text-sm">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Tujuan</th>
                                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Konteks</th>
                                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Pesan</th>
                                <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse ($messages as $msg)
                                @php
                                    $statusConf = match($msg->status) {
                                        'sent'    => ['color' => 'text-green-700', 'bg' => 'bg-green-100', 'label' => 'Terkirim'],
                                        'pending' => ['color' => 'text-yellow-700', 'bg' => 'bg-yellow-100', 'label' => 'Diproses'],
                                        'failed'  => ['color' => 'text-red-700', 'bg' => 'bg-red-100', 'label' => 'Gagal'],
                                    };
                                @endphp
                                <tr class="hover:bg-gray-50 transition" x-data="{ expanded: false }">
                                    <td class="px-5 py-3.5 align-top">
                                        <div class="font-medium text-gray-800">{{ $msg->contact->name ?? '—' }}</div>
                                        <div class="text-xs text-gray-500 font-mono mt-0.5">{{ $msg->phone }}</div>
                                    </td>
                                    <td class="px-5 py-3.5 align-top">
                                        <div class="mb-1.5 flex flex-wrap gap-1">
                                            @if($msg->reminderLog)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-100">
                                                    Otomatis
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-100">
                                                    Manual
                                                </span>
                                            @endif
                                        </div>

                                        @if($msg->reminderLog)
                                            <a href="{{ route('licenses.show', $msg->reminderLog->license_id) }}" class="font-medium text-slate-900 hover:text-red-600 transition hover:underline">
                                                {{ Str::limit($msg->reminderLog->license->name, 30) }}
                                            </a>
                                            <div class="text-xs text-slate-500 mt-0.5">
                                                Milestone: {{ $msg->reminderLog->milestone >= 0 ? 'H-'.$msg->reminderLog->milestone : 'H+'.abs($msg->reminderLog->milestone) }}
                                            </div>
                                        @elseif($msg->contact && $msg->contact->license)
                                            <a href="{{ route('licenses.show', $msg->contact->license_id) }}" class="font-medium text-slate-900 hover:text-red-600 transition hover:underline">
                                                {{ Str::limit($msg->contact->license->name, 30) }}
                                            </a>
                                            <div class="text-xs text-slate-500 mt-0.5">
                                                Jatuh tempo terdekat
                                            </div>
                                        @else
                                            <span class="text-slate-500 font-medium italic">Tes Koneksi</span>
                                        @endif
                                        <div class="text-xs text-gray-400 mt-1" title="{{ $msg->created_at }}">
                                            Dibuat: {{ $msg->created_at->format('d/m/Y H:i') }}
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 align-top">
                                        <div class="relative">
                                            <div class="text-gray-600 whitespace-pre-wrap text-xs" :class="expanded ? '' : 'line-clamp-3'">{{ $msg->body }}</div>
                                            <button @click="expanded = !expanded" class="text-red-600 hover:text-red-800 text-xs font-medium mt-1">
                                                <span x-show="!expanded">Tampilkan semua</span>
                                                <span x-show="expanded">Sembunyikan</span>
                                            </button>
                                        </div>
                                        @if($msg->error_message)
                                            <div class="mt-2 bg-red-50 text-red-600 text-xs p-2 rounded border border-red-100">
                                                <span class="font-semibold">Error:</span> {{ $msg->error_message }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 align-top text-center">
                                        <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium {{ $statusConf['bg'] }} {{ $statusConf['color'] }}">
                                            {{ $statusConf['label'] }}
                                        </span>
                                        @if($msg->sent_at)
                                            <div class="text-xs text-gray-500 mt-1">{{ $msg->sent_at->format('d/m H:i') }}</div>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 align-top text-center">
                                        @if($msg->status === 'failed')
                                            <form method="POST" action="{{ route('reminders.retry', $msg) }}">
                                                @csrf
                                                <button type="submit" class="text-xs font-medium bg-white border border-gray-300 text-gray-700 px-3 py-1.5 rounded shadow-sm hover:bg-gray-50 transition">
                                                    Coba Lagi
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-8 text-center text-gray-500">
                                        Belum ada riwayat pesan WhatsApp.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if ($messages->hasPages())
                    <div class="px-5 py-4 border-t border-gray-100">
                        {{ $messages->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
