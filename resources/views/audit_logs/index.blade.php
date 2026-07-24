<x-app-layout>
    <x-slot name="title">Audit Log</x-slot>
    <x-slot name="header">
        <h1 class="font-display text-xl sm:text-2xl font-semibold text-gray-900 tracking-tight leading-snug">Audit Log</h1>
        <p class="text-sm text-gray-500 mt-1">Catatan aktivitas dan riwayat keamanan sistem</p>
    </x-slot>

    <div class="px-4 sm:px-6 lg:px-8 py-6">
        <div class="max-w-7xl mx-auto space-y-6">

            <!-- Filters Card -->
            <div class="bg-white border border-gray-200/70 rounded-xl shadow-sm p-6">
                <form method="GET" action="{{ route('audit-logs.index') }}">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label for="user_id" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Admin</label>
                            <select name="user_id" id="user_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-red-500 focus:border-red-500 transition">
                                <option value="">Semua Admin</option>
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="action" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Aksi</label>
                            <select name="action" id="action" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-red-500 focus:border-red-500 transition">
                                <option value="">Semua Aksi</option>
                                @foreach($actions as $act)
                                    <option value="{{ $act }}" {{ request('action') == $act ? 'selected' : '' }}>{{ $act }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="date_from" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Dari Tanggal</label>
                            <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-red-500 focus:border-red-500 transition">
                        </div>

                        <div>
                            <label for="date_to" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Sampai Tanggal</label>
                            <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-red-500 focus:border-red-500 transition">
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 border-t border-gray-100 pt-4 mt-5">
                        @if(request()->hasAny(['user_id', 'action', 'date_from', 'date_to']))
                            <a href="{{ route('audit-logs.index') }}" class="bg-gray-100 text-gray-600 px-5 py-2 rounded-lg text-sm hover:bg-gray-200 transition font-medium">Reset</a>
                        @endif
                        <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-lg text-sm hover:bg-red-700 transition font-medium shadow-sm">Filter</button>
                    </div>
                </form>
            </div>

            <!-- Audit Logs Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200/70 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] text-sm text-slate-700">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Waktu</th>
                                <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Admin</th>
                                <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                                <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Deskripsi</th>
                                <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">IP Address</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($logs as $log)
                                @php
                                    $actionConf = match($log->action) {
                                        'login'              => ['color' => 'bg-green-50 text-green-700 border-green-200', 'label' => 'Login Sukses'],
                                        'login_failed'       => ['color' => 'bg-red-50 text-red-700 border-red-200', 'label' => 'Login Gagal'],
                                        'logout'             => ['color' => 'bg-gray-50 text-gray-700 border-gray-200', 'label' => 'Logout'],
                                        'license.created'    => ['color' => 'bg-blue-50 text-blue-700 border-blue-200', 'label' => 'Buat Lisensi'],
                                        'license.updated'    => ['color' => 'bg-amber-50 text-amber-700 border-amber-200', 'label' => 'Ubah Lisensi'],
                                        'license.deleted'    => ['color' => 'bg-red-50 text-red-700 border-red-200', 'label' => 'Hapus Lisensi'],
                                        'file.downloaded'    => ['color' => 'bg-indigo-50 text-indigo-700 border-indigo-200', 'label' => 'Unduh File'],
                                        'file.deleted'       => ['color' => 'bg-rose-50 text-rose-700 border-rose-200', 'label' => 'Hapus File'],
                                        'reminder.sent_manual'=> ['color' => 'bg-teal-50 text-teal-700 border-teal-200', 'label' => 'Kirim Manual'],
                                        'user.created'       => ['color' => 'bg-purple-50 text-purple-700 border-purple-200', 'label' => 'Tambah Admin'],
                                        'user.deactivated'   => ['color' => 'bg-slate-100 text-slate-700 border-slate-200', 'label' => 'Deaktivasi'],
                                        'user.activated'     => ['color' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'label' => 'Aktivasi'],
                                        'user.password_reset'=> ['color' => 'bg-cyan-50 text-cyan-700 border-cyan-200', 'label' => 'Reset Sandi'],
                                        'settings.updated'   => ['color' => 'bg-violet-50 text-violet-700 border-violet-200', 'label' => 'Ubah Setelan'],
                                        default              => ['color' => 'bg-gray-100 text-gray-700 border-gray-200', 'label' => $log->action],
                                    };
                                @endphp
                                <tr class="hover:bg-gray-50/50 transition">
                                    <td class="px-5 py-4 text-xs font-mono text-slate-500 whitespace-nowrap">
                                        {{ $log->created_at->format('d/m/Y H:i:s') }}
                                    </td>
                                    <td class="px-5 py-4">
                                        @if($log->user)
                                            <div class="font-medium text-slate-800">{{ $log->user->name }}</div>
                                            <div class="text-[10px] text-slate-400 font-mono">{{ $log->user->email }}</div>
                                        @else
                                            <span class="text-slate-400 italic">Sistem / Pengunjung</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold border {{ $actionConf['color'] }}">
                                            {{ $actionConf['label'] }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-xs text-slate-600 max-w-xs md:max-w-md">
                                        {{ $log->description ?? '—' }}
                                    </td>
                                    <td class="px-5 py-4 text-xs font-mono text-slate-500 whitespace-nowrap">
                                        {{ $log->ip_address ?? '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-8 text-center text-gray-400">Tidak ada log aktivitas audit yang tercatat.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($logs->hasPages())
                    <div class="px-5 py-4 border-t border-gray-100">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
