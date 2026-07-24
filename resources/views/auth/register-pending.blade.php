<x-guest-layout>
    <x-slot name="title">Menunggu Persetujuan</x-slot>

    <div class="space-y-6 text-center">
        <div class="mx-auto flex items-center justify-center w-14 h-14 rounded-full bg-amber-50 border border-amber-200">
            <svg class="w-7 h-7 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>

        <div>
            <h2 class="text-2xl font-semibold text-slate-900 tracking-tight">Pendaftaran berhasil</h2>
            <p class="text-sm text-slate-500 mt-3 leading-relaxed">
                Akun Anda <strong class="text-slate-700">{{ $email }}</strong> menunggu persetujuan admin utama.
                Anda akan dapat masuk setelah disetujui.
            </p>
            <p class="text-xs text-slate-400 mt-2">
                Pemberitahuan akan dikirimkan ke email Anda setelah pengajuan diputuskan.
            </p>
        </div>

        <a href="{{ route('login') }}"
           class="inline-flex items-center justify-center gap-2 py-2.5 px-6 rounded-lg text-sm font-semibold text-white bg-slate-900 hover:bg-slate-800 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-slate-700 focus:ring-offset-2 transition-all shadow-sm">
            Kembali ke halaman masuk
        </a>
    </div>
</x-guest-layout>
