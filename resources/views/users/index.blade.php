<x-app-layout>
    <x-slot name="title">Kelola Admin</x-slot>
    <x-slot name="header">
        <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">Kelola Admin</h1>
        <p class="text-sm text-gray-500 mt-1">Persetujuan pendaftaran & kontrol akses administrator</p>
    </x-slot>

    <div class="px-4 sm:px-6 lg:px-8 py-6" x-data="{
        activeTab: '{{ $pendingUsers->isNotEmpty() && !request()->filled('search') ? 'pending' : 'admins' }}',
        showRejectModal: false,
        rejectActionUrl: '',
        rejectName: '',
        showPromoteModal: false,
        promoteActionUrl: '',
        promoteName: ''
    }">
        <div class="max-w-7xl mx-auto space-y-6">

            <!-- Success/Error Alerts -->
            @if(session('success'))
                <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 border border-green-100 flex items-center gap-2" role="alert">
                    <svg class="w-4 h-4 text-green-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 border border-red-100 flex items-center gap-2" role="alert">
                    <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <span class="font-medium">{{ session('error') }}</span>
                </div>
            @endif

            <!-- Tabs -->
            <div class="flex gap-4 border-b border-gray-200 pb-1">
                <button @click="activeTab = 'pending'"
                        :class="activeTab === 'pending' ? 'border-red-600 text-red-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="pb-3 border-b-2 text-sm transition focus:outline-none flex items-center gap-1.5">
                    Pengajuan Pendaftaran
                    @if($pendingUsers->isNotEmpty())
                        <span class="inline-flex items-center justify-center min-w-5 h-5 px-1.5 rounded-full text-[11px] font-bold bg-red-600 text-white">{{ $pendingUsers->count() }}</span>
                    @endif
                </button>
                <button @click="activeTab = 'admins'"
                        :class="activeTab === 'admins' ? 'border-red-600 text-red-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="pb-3 border-b-2 text-sm transition focus:outline-none">
                    Daftar Administrator ({{ $users->total() }})
                </button>
            </div>

            <!-- ==================== TAB 1: PENDING REGISTRATIONS ==================== -->
            <div x-show="activeTab === 'pending'" class="space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200/70 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[750px] text-sm text-slate-700">
                            <thead class="bg-gray-50 border-b border-gray-100">
                                <tr>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama &amp; Email</th>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Nomor WhatsApp</th>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal Mendaftar</th>
                                    <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($pendingUsers as $pending)
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="px-5 py-4">
                                            <div class="font-semibold text-slate-800">{{ $pending->name }}</div>
                                            <div class="text-xs text-slate-400 mt-0.5">{{ $pending->email }}</div>
                                        </td>
                                        <td class="px-5 py-4 font-mono text-xs text-slate-600">{{ $pending->phone ?? '—' }}</td>
                                        <td class="px-5 py-4 text-xs text-slate-500">
                                            {{ $pending->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB
                                        </td>
                                        <td class="px-5 py-4 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <form method="POST" action="{{ route('users.approve', $pending) }}" class="inline">
                                                    @csrf
                                                    <button type="submit"
                                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-green-600 hover:bg-green-700 active:scale-[0.98] transition shadow-sm">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                                        Setujui
                                                    </button>
                                                </form>
                                                <button @click="
                                                    rejectActionUrl = '{{ route('users.reject', $pending) }}';
                                                    rejectName = '{{ $pending->name }}';
                                                    showRejectModal = true;
                                                " class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-red-700 bg-red-50 border border-red-200 hover:bg-red-100 active:scale-[0.98] transition">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    Tolak
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-5 py-8 text-center text-gray-400">Tidak ada pengajuan pendaftaran yang menunggu.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ==================== TAB 2: ADMINISTRATOR LIST ==================== -->
            <div x-show="activeTab === 'admins'" x-cloak class="space-y-6">
                <!-- Search -->
                <form method="GET" action="{{ route('users.index') }}" class="flex-1 max-w-md flex items-center gap-2">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 flex items-center pointer-events-none" style="left: 1rem;">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </span>
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Cari nama atau email..."
                               class="w-full pr-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:ring-red-500 focus:border-red-500 transition"
                               style="padding-left: 2.75rem;">
                    </div>
                    <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-700 transition">Filter</button>
                    @if(request()->filled('search'))
                        <a href="{{ route('users.index') }}" class="text-center bg-gray-100 text-gray-600 px-4 py-2 rounded-lg text-sm hover:bg-gray-200 transition">Reset</a>
                    @endif
                </form>

                <!-- Admins Table Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200/70 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[900px] text-sm text-slate-700">
                            <thead class="bg-gray-50 border-b border-gray-100">
                                <tr>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama &amp; Email</th>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Nomor WhatsApp</th>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Peran</th>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Terakhir Login</th>
                                    <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($users as $user)
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="px-5 py-4">
                                            <div class="font-semibold text-slate-800">
                                                {{ $user->name }}
                                                @if($user->id === Auth::id())
                                                    <span class="text-[10px] font-bold text-slate-400">(Anda)</span>
                                                @endif
                                            </div>
                                            <div class="text-xs text-slate-400 mt-0.5">{{ $user->email }}</div>
                                        </td>
                                        <td class="px-5 py-4 font-mono text-xs text-slate-600">{{ $user->phone ?? '—' }}</td>
                                        <td class="px-5 py-4">
                                            @if($user->is_super_admin)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd"/></svg>
                                                    Admin Utama
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-50 text-slate-600 border border-slate-200">
                                                    Administrator
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4">
                                            @if($user->status === \App\Models\User::STATUS_ACTIVE)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">Aktif</span>
                                            @elseif($user->status === \App\Models\User::STATUS_REJECTED)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200">Ditolak</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">Nonaktif</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4 text-xs text-slate-500 font-mono">
                                            {{ $user->last_login_at ? $user->last_login_at->format('d/m/Y H:i') : 'Belum pernah login' }}
                                        </td>
                                        <td class="px-5 py-4 text-center">
                                            <div class="flex items-center justify-center gap-3">
                                                <!-- Send Password Reset Link -->
                                                @if($user->status === \App\Models\User::STATUS_ACTIVE)
                                                    <form method="POST" action="{{ route('users.send-reset-link', $user) }}" onsubmit="return confirm('Kirim link reset password ke email admin ini?')" class="inline">
                                                        @csrf
                                                        <button type="submit" class="text-slate-500 hover:text-slate-800 transition font-medium" title="Kirim Link Reset Password">
                                                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                                                        </button>
                                                    </form>
                                                @endif

                                                <!-- Promote to Super Admin -->
                                                @if(!$user->is_super_admin && $user->status === \App\Models\User::STATUS_ACTIVE)
                                                    <button @click="
                                                        promoteActionUrl = '{{ route('users.make-super-admin', $user) }}';
                                                        promoteName = '{{ $user->name }}';
                                                        showPromoteModal = true;
                                                    " class="text-indigo-500 hover:text-indigo-700 transition font-medium" title="Jadikan Admin Utama">
                                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>
                                                    </button>
                                                @endif

                                                <!-- Toggle Status (Deactivate / Activate) -->
                                                @if($user->id !== Auth::id())
                                                    <form method="POST" action="{{ route('users.toggle', $user) }}" onsubmit="return confirm('{{ $user->status === \App\Models\User::STATUS_ACTIVE ? 'Nonaktifkan admin ini? Ia tidak akan bisa login sampai diaktifkan kembali.' : 'Aktifkan admin ini? Ia akan bisa login kembali.' }}')" class="inline">
                                                        @csrf
                                                        <button type="submit" class="transition font-medium {{ $user->status === \App\Models\User::STATUS_ACTIVE ? 'text-red-600 hover:text-red-800' : 'text-green-600 hover:text-green-800' }}" title="{{ $user->status === \App\Models\User::STATUS_ACTIVE ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                            @if($user->status === \App\Models\User::STATUS_ACTIVE)
                                                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                                            @else
                                                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                            @endif
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-gray-300 cursor-not-allowed" title="Anda tidak dapat menonaktifkan akun Anda sendiri">
                                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-5 py-8 text-center text-gray-400">Tidak ada data administrator.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($users->hasPages())
                        <div class="px-5 py-4 border-t border-gray-100">
                            {{ $users->links() }}
                        </div>
                    @endif
                </div>
            </div>

        </div>

        <!-- Reject Confirmation Modal -->
        <div class="fixed inset-0 z-50 overflow-y-auto" x-show="showRejectModal" style="display: none;" x-transition>
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" @click="showRejectModal = false"></div>
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-gray-100"
                     @click.away="showRejectModal = false">
                    <form method="POST" :action="rejectActionUrl">
                        @csrf
                        <div class="bg-white px-6 pt-6 pb-4">
                            <h3 class="text-lg font-semibold leading-6 text-gray-900">Tolak Pengajuan</h3>
                            <p class="text-sm text-gray-500 mt-3">
                                Tolak pengajuan pendaftaran dari <strong class="text-red-600" x-text="rejectName"></strong>?
                                Akun ini tidak akan dapat digunakan untuk masuk, dan pendaftar akan menerima email pemberitahuan.
                            </p>
                        </div>
                        <div class="bg-gray-50 px-6 py-3 sm:flex sm:flex-row-reverse gap-2">
                            <button type="submit" class="inline-flex w-full justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 transition sm:w-auto">Ya, Tolak</button>
                            <button type="button" @click="showRejectModal = false" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 transition sm:mt-0 sm:w-auto">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Promote to Super Admin Confirmation Modal -->
        <div class="fixed inset-0 z-50 overflow-y-auto" x-show="showPromoteModal" style="display: none;" x-transition>
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" @click="showPromoteModal = false"></div>
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-gray-100"
                     @click.away="showPromoteModal = false">
                    <form method="POST" :action="promoteActionUrl">
                        @csrf
                        <div class="bg-white px-6 pt-6 pb-4">
                            <h3 class="text-lg font-semibold leading-6 text-gray-900">Jadikan Admin Utama</h3>
                            <p class="text-sm text-gray-500 mt-3">
                                Angkat <strong class="text-indigo-600" x-text="promoteName"></strong> menjadi admin utama?
                            </p>
                            <p class="text-sm text-gray-500 mt-2">
                                Sebagai admin utama, ia akan dapat <strong>menyetujui atau menolak pendaftaran</strong> serta
                                <strong>mengelola administrator lain</strong> (mengaktifkan, menonaktifkan, dan mengangkat admin utama baru).
                            </p>
                        </div>
                        <div class="bg-gray-50 px-6 py-3 sm:flex sm:flex-row-reverse gap-2">
                            <button type="submit" class="inline-flex w-full justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 transition sm:w-auto">Ya, Jadikan Admin Utama</button>
                            <button type="button" @click="showPromoteModal = false" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 transition sm:mt-0 sm:w-auto">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
