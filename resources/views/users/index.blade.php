<x-app-layout>
    <x-slot name="title">Kelola Pengguna</x-slot>
    <x-slot name="header">
        <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">Kelola Pengguna</h1>
        <p class="text-sm text-gray-500 mt-1">Daftar administrator sistem & kontrol akses</p>
    </x-slot>

    <div class="px-4 sm:px-6 lg:px-8 py-6" x-data="{ 
        showCreateModal: false, 
        showResetModal: false,
        resetActionUrl: '',
        resetAdminName: '',
        activeTab: 'admins'
    }">
        <div class="max-w-7xl mx-auto space-y-6">

            <!-- Success/Error Alerts -->
            @if(session('success'))
                <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 border border-green-100 flex items-center justify-between" role="alert">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="font-medium">{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 border border-red-100 flex items-center justify-between" role="alert">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                        <span class="font-medium">{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            @if($errors->any())
                <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 border border-red-100" role="alert">
                    <div class="font-medium mb-1">Periksa kembali input Anda:</div>
                    <ul class="list-disc list-inside text-xs space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Tabs & Primary Button -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 border-b border-gray-200 pb-1">
                <div class="flex gap-4">
                    <button @click="activeTab = 'admins'" 
                            :class="activeTab === 'admins' ? 'border-red-600 text-red-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="pb-3 border-b-2 text-sm transition focus:outline-none">
                        Daftar Admin ({{ $users->total() }})
                    </button>
                    <button @click="activeTab = 'invitations'" 
                            :class="activeTab === 'invitations' ? 'border-red-600 text-red-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="pb-3 border-b-2 text-sm transition focus:outline-none flex items-center gap-1.5">
                        Undangan Tertunda ({{ count($pendingInvitations) }})
                    </button>
                </div>

                <button @click="showCreateModal = true" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-700 transition flex items-center justify-center gap-2 shadow-sm font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Undang Admin
                </button>
            </div>

            <!-- ==================== TAB 1: ADMINS LIST ==================== -->
            <div x-show="activeTab === 'admins'" class="space-y-6">
                <!-- Search & Filters -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
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
                </div>

                <!-- Users Table Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200/70 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[850px] text-sm text-slate-700">
                            <thead class="bg-gray-50 border-b border-gray-100">
                                <tr>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama &amp; Email</th>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Kontak (WhatsApp)</th>
                                    <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Verifikasi</th>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Terakhir Login</th>
                                    <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($users as $user)
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="px-5 py-4">
                                            <div class="font-semibold text-slate-800">{{ $user->name }}</div>
                                            <div class="text-xs text-slate-400 mt-0.5">{{ $user->email }}</div>
                                        </td>
                                        <td class="px-5 py-4 text-xs font-mono text-slate-600">
                                            {{ $user->phone ?? '—' }}
                                        </td>
                                        <td class="px-5 py-4 text-center">
                                            <div class="flex items-center justify-center gap-3 text-xs font-semibold">
                                                <span class="inline-flex items-center gap-1 {{ $user->email_verified_at ? 'text-green-600 bg-green-50 px-1.5 py-0.5 rounded border border-green-200' : 'text-slate-400 bg-slate-50 px-1.5 py-0.5 rounded border border-slate-200' }}">
                                                    Email {{ $user->email_verified_at ? '✓' : '—' }}
                                                </span>
                                                <span class="inline-flex items-center gap-1 {{ $user->phone_verified_at ? 'text-green-600 bg-green-50 px-1.5 py-0.5 rounded border border-green-200' : 'text-slate-400 bg-slate-50 px-1.5 py-0.5 rounded border border-slate-200' }}">
                                                    WA {{ $user->phone_verified_at ? '✓' : '—' }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-5 py-4">
                                            @if($user->is_active)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                                                    Aktif
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">
                                                    Nonaktif
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4 text-xs text-slate-500 font-mono">
                                            {{ $user->last_login_at ? $user->last_login_at->format('d/m/Y H:i') : 'Belum pernah login' }}
                                        </td>
                                        <td class="px-5 py-4 text-center">
                                            <div class="flex items-center justify-center gap-3">
                                                <!-- Reset Password Link -->
                                                <button @click="
                                                    resetActionUrl = '{{ route('users.reset-password', $user) }}';
                                                    resetAdminName = '{{ $user->name }}';
                                                    showResetModal = true;
                                                " class="text-slate-500 hover:text-slate-800 transition font-medium" title="Reset Password">
                                                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m-5-4v1a3 3 0 00-3 3h1M4 12a8 8 0 018-8v8H4z"/></svg>
                                                </button>

                                                <!-- Toggle Status (Deactivate / Activate) -->
                                                @if($user->id !== Auth::id())
                                                    <form method="POST" action="{{ route('users.toggle', $user) }}" onsubmit="return confirm('Apakah Anda yakin ingin mengubah status admin ini?')" class="inline">
                                                        @csrf
                                                        <button type="submit" class="transition font-medium {{ $user->is_active ? 'text-red-600 hover:text-red-800' : 'text-green-600 hover:text-green-800' }}" title="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                            @if($user->is_active)
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

            <!-- ==================== TAB 2: PENDING INVITATIONS ==================== -->
            <div x-show="activeTab === 'invitations'" class="space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200/70 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[750px] text-sm text-slate-700">
                            <thead class="bg-gray-50 border-b border-gray-100">
                                <tr>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama &amp; Email</th>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Nomor WhatsApp</th>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Diundang Oleh</th>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Masa Berlaku</th>
                                    <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($pendingInvitations as $invite)
                                    @php
                                        $isExpired = $invite->isExpired();
                                        $hoursLeft = now()->diffInHours($invite->expires_at, false);
                                    @endphp
                                    <tr class="hover:bg-gray-50/50 transition {{ $isExpired ? 'bg-red-50/20' : '' }}">
                                        <td class="px-5 py-4">
                                            <div class="font-semibold text-slate-800">{{ $invite->name }}</div>
                                            <div class="text-xs text-slate-400 mt-0.5">{{ $invite->email }}</div>
                                        </td>
                                        <td class="px-5 py-4 font-mono text-xs text-slate-600">
                                            {{ $invite->phone }}
                                        </td>
                                        <td class="px-5 py-4 text-xs text-slate-500">
                                            {{ $invite->invitedBy ? $invite->invitedBy->name : 'System' }}
                                        </td>
                                        <td class="px-5 py-4">
                                            @if ($isExpired)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 border border-red-200">
                                                    Kadaluarsa
                                                </span>
                                            @else
                                                <span class="text-slate-600 font-medium text-xs">
                                                    {{ $hoursLeft }} jam lagi
                                                </span>
                                                <div class="text-[10px] text-slate-400 mt-0.5">Sampai {{ $invite->expires_at->format('d/m H:i') }}</div>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4 text-center">
                                            <div class="flex items-center justify-center gap-3">
                                                <!-- Resend Invitation -->
                                                <form method="POST" action="{{ route('users.invitations.resend', $invite) }}" onsubmit="return confirm('Kirim ulang email undangan ke admin ini?')">
                                                    @csrf
                                                    <button type="submit" class="text-slate-500 hover:text-slate-800 transition font-medium" title="Kirim Ulang Undangan">
                                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                                        </svg>
                                                    </button>
                                                </form>

                                                <!-- Cancel Invitation -->
                                                <form method="POST" action="{{ route('users.invitations.cancel', $invite) }}" onsubmit="return confirm('Batalkan undangan untuk admin ini?')">
                                                    @csrf
                                                    <button type="submit" class="text-red-600 hover:text-red-800 transition font-medium" title="Batalkan Undangan">
                                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-5 py-8 text-center text-gray-400">Tidak ada undangan yang tertunda.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <!-- Alpine.js Modals -->
        
        <!-- Undang Admin Modal -->
        <div class="fixed inset-0 z-50 overflow-y-auto" x-show="showCreateModal" style="display: none;" x-transition>
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" @click="showCreateModal = false"></div>
            
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-gray-100 animate-in fade-in zoom-in-95 duration-200"
                     @click.away="showCreateModal = false">
                    <form method="POST" action="{{ route('users.store') }}">
                        @csrf
                        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="w-full text-left">
                                    <h3 class="text-lg font-semibold leading-6 text-gray-900 border-b border-gray-100 pb-3" id="modal-title">Undang Admin Baru</h3>
                                    <p class="text-xs text-slate-400 mt-1">Sistem akan membuat tautan undangan registrasi unik dan mengirimkannya ke alamat email bersangkutan.</p>
                                    
                                    <div class="mt-4 space-y-4">
                                        <div>
                                            <label for="name" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                                            <input type="text" name="name" id="name" required placeholder="Contoh: Budi Santoso" class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500/40 focus:border-red-500 transition">
                                        </div>
                                        
                                        <div>
                                            <label for="email" class="block text-sm font-medium text-gray-700">Email Tujuan</label>
                                            <input type="email" name="email" id="email" required placeholder="nama@company.com" class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500/40 focus:border-red-500 transition">
                                        </div>

                                        <div>
                                            <label for="phone" class="block text-sm font-medium text-gray-700">Nomor WhatsApp</label>
                                            <input type="text" name="phone" id="phone" required placeholder="08xxxxxxxxxx atau 628xxxxxxxxxx" class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500/40 focus:border-red-500 transition">
                                            <p class="text-[10px] text-slate-400 mt-1">Digunakan untuk opsi penerimaan kode keamanan OTP saat calon admin registrasi.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 gap-2">
                            <button type="submit" class="inline-flex w-full justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 transition sm:w-auto">Kirim Undangan</button>
                            <button type="button" @click="showCreateModal = false" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 transition sm:mt-0 sm:w-auto">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Reset Password Modal -->
        <div class="fixed inset-0 z-50 overflow-y-auto" x-show="showResetModal" style="display: none;" x-transition>
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" @click="showResetModal = false"></div>
            
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-gray-100 animate-in fade-in zoom-in-95 duration-200"
                     @click.away="showResetModal = false">
                    <form method="POST" :action="resetActionUrl">
                        @csrf
                        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="w-full text-left">
                                    <h3 class="text-lg font-semibold leading-6 text-gray-900 border-b border-gray-100 pb-3">Reset Password Admin</h3>
                                    <p class="text-xs text-gray-500 mt-2 font-medium">Mengubah password untuk admin: <strong class="text-red-600" x-text="resetAdminName"></strong></p>
                                    
                                    <div class="mt-4 space-y-4">
                                        <div>
                                            <label for="reset_password" class="block text-sm font-medium text-gray-700">Password Baru</label>
                                            <input type="password" name="password" id="reset_password" required class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500/40 focus:border-red-500 transition">
                                            <p class="text-slate-400 text-[11px] mt-1">Minimal 8 karakter.</p>
                                        </div>

                                        <div>
                                            <label for="reset_password_confirmation" class="block text-sm font-medium text-gray-700">Konfirmasi Password Baru</label>
                                            <input type="password" name="password_confirmation" id="reset_password_confirmation" required class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500/40 focus:border-red-500 transition">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 gap-2">
                            <button type="submit" class="inline-flex w-full justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 transition sm:w-auto">Reset Password</button>
                            <button type="button" @click="showResetModal = false" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 transition sm:mt-0 sm:w-auto">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
