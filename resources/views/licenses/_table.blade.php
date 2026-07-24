@php
    $emptyTitle = $emptyTitle ?? 'Belum ada lisensi tercatat';
    $emptySubtitle = $emptySubtitle ?? 'Mulai dengan menambahkan lisensi pertama.';
@endphp

@if ($licenses->isEmpty())
    <div class="py-16 text-center">
        <svg class="w-24 h-20 text-gray-200 mx-auto mb-5" fill="none" viewBox="0 0 160 120" aria-hidden="true">
            <rect x="24" y="22" width="112" height="76" rx="10" class="fill-gray-100"/>
            <path d="M48 48h64M48 64h46M48 80h28" class="stroke-gray-300" stroke-width="6" stroke-linecap="round"/>
            <circle cx="120" cy="36" r="14" class="fill-red-100"/>
            <path d="M114 36h12M120 30v12" class="stroke-red-500" stroke-width="3" stroke-linecap="round"/>
        </svg>
        <h3 class="font-display text-[15px] font-semibold text-gray-700 tracking-tight">{{ $emptyTitle }}</h3>
        <p class="text-sm text-gray-400 mt-1">{{ $emptySubtitle }}</p>
        <a href="{{ route('licenses.create') }}" class="mt-5 inline-flex items-center gap-2 bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-700 hover:brightness-110 active:scale-[0.98] transition-all duration-150 shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Lisensi Pertama
        </a>
    </div>
@else
    <div class="overflow-x-auto xl:overflow-x-visible max-h-[680px]">
        <table class="w-full min-w-[960px] xl:min-w-0 table-fixed text-sm">
            <colgroup>
                <col class="w-[19%]">
                <col class="w-[12%]">
                <col class="w-[20%]">
                <col class="w-[13%]">
                <col class="w-[15%]">
                <col class="w-[10%]">
                <col class="w-[11%]">
            </colgroup>
            <thead class="bg-white border-b border-gray-200 sticky top-0 z-10">
                <tr>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Nama Lisensi</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Vendor</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">PIC Utama</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Tgl. Berakhir</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Sisa Hari</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                    <th class="text-center px-2 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($licenses as $license)
                    @php
                        $primaryContact = $license->contacts->firstWhere('is_primary', true) ?? $license->contacts->first();
                        $days = $license->daysRemaining;
                        $health = $license->healthStatus;
                        $totalDays = max(1, $license->start_date->diffInDays($license->end_date));
                        $remaining = max(0, min($totalDays, $days));
                        $progress = (int) round(($remaining / $totalDays) * 100);
                        $status = [
                            'aman'    => ['class' => 'text-green-600 font-bold', 'label' => 'Aman'],
                            'waspada' => ['class' => 'text-yellow-600 font-bold', 'label' => 'Waspada'],
                            'kritis'  => ['class' => 'text-orange-600 font-bold', 'label' => 'Kritis'],
                            'expired' => ['class' => 'text-red-600 font-bold', 'label' => 'Expired'],
                        ][$health] ?? ['class' => 'text-gray-600 font-bold', 'label' => ucfirst($health)];

                        if ($primaryContact) {
                            $colors = [
                                'bg-red-50 text-red-700 border-red-100',
                                'bg-blue-50 text-blue-700 border-blue-100',
                                'bg-emerald-50 text-emerald-700 border-emerald-100',
                                'bg-amber-50 text-amber-700 border-amber-100',
                                'bg-purple-50 text-purple-700 border-purple-100',
                                'bg-rose-50 text-rose-700 border-rose-100',
                            ];
                            $colorClass = $colors[abs(crc32($primaryContact->name)) % count($colors)];
                        }
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-4 py-3">
                            <a href="{{ route('licenses.show', $license) }}" class="font-semibold text-gray-900 hover:text-red-600 transition block truncate">
                                {{ $license->name }}
                            </a>
                            @if ($license->description)
                                <p class="text-xs text-gray-400 truncate mt-0.5">{{ $license->description }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600 truncate">
                            {{ $license->vendor ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            @if ($primaryContact)
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="w-7 h-7 rounded-full border {{ $colorClass }} flex items-center justify-center flex-shrink-0 text-xs font-bold">
                                        {{ strtoupper(substr($primaryContact->name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-medium text-gray-800 truncate text-xs">{{ $primaryContact->name }}</p>
                                        <p class="text-gray-400 text-[11px] font-mono truncate">{{ $primaryContact->phone }}</p>
                                    </div>
                                </div>
                            @else
                                <span class="text-gray-400 text-xs italic">Belum ada</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600 font-mono text-xs whitespace-nowrap">
                            {{ $license->end_date ? $license->end_date->translatedFormat('d/m/Y') : '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-col gap-1 mb-1.5">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-bold text-xs {{ $days <= 0 ? 'text-red-600' : ($days <= 7 ? 'text-red-600' : ($days <= 30 ? 'text-orange-600' : ($days <= 90 ? 'text-yellow-600' : 'text-green-600'))) }}">
                                        {{ abs($days) }} <span class="font-normal text-gray-400">{{ $days >= 0 ? 'hari' : 'hari lalu' }}</span>
                                    </span>
                                </div>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-300 {{ $days <= 0 ? 'bg-red-500 w-full' : ($days <= 7 ? 'bg-red-600 animate-pulse' : ($days <= 30 ? 'bg-orange-500' : ($days <= 90 ? 'bg-yellow-500' : 'bg-green-500'))) }}"
                                     style="width: {{ $days <= 0 ? 100 : $progress }}%"></div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-xs {{ $status['class'] }}">
                                {{ $status['label'] }}
                            </span>
                        </td>
                        <td class="px-2 py-3">
                            <div class="flex items-center justify-center gap-1">
                                <a href="{{ route('licenses.show', $license) }}" title="Lihat detail" class="p-2 rounded-lg text-red-600 hover:bg-gray-100 active:scale-[0.98] transition-all duration-150">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <form method="POST" action="{{ route('licenses.destroy', $license) }}" onsubmit="confirmDeleteLicense(event, this)" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" title="Hapus lisensi" class="p-2 rounded-lg text-red-500 hover:bg-red-50 hover:text-red-700 active:scale-[0.98] transition-all duration-150">
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

    <div class="px-6 py-4 border-t border-gray-100 bg-white">
        {{ $licenses->links() }}
    </div>
@endif
