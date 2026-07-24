<x-guest-layout>
    <x-slot name="title">Lupa Kata Sandi</x-slot>

    <div x-data="{ loading: false }" class="space-y-6">
        <div>
            <h2 class="font-display text-lg sm:text-xl font-semibold text-slate-900 tracking-tight leading-snug">Lupa kata sandi?</h2>
            <p class="text-sm text-slate-400 mt-1">
                Masukkan email Anda. Kami akan mengirimkan tautan untuk membuat kata sandi baru (berlaku 60 menit).
            </p>
        </div>

        <!-- Session Status -->
        @if (session('status'))
            <div class="text-sm text-emerald-600 bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3">
                {{ session('status') }}
            </div>
        @endif

        @include('auth.partials.error-banner', ['title' => 'Permintaan gagal'])

        <form method="POST" action="{{ route('password.email') }}" @submit="loading = true" class="space-y-4">
            @csrf

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                <input id="email" name="email" type="email" autocomplete="email" required
                       value="{{ old('email') }}" autofocus
                       placeholder="nama@hariff.co.id"
                       class="block w-full px-4 py-2.5 border border-gray-200 rounded-lg text-slate-900 placeholder-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 transition">
            </div>

            <!-- Submit Button -->
            <button type="submit" :disabled="loading"
                    class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg text-sm font-semibold text-white bg-slate-900 hover:bg-slate-800 disabled:bg-slate-700 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-slate-700 focus:ring-offset-2 transition-all shadow-sm">
                <svg x-show="loading" x-cloak class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span x-text="loading ? 'Mengirim...' : 'Kirim Tautan Reset'">Kirim Tautan Reset</span>
            </button>
        </form>

        <p class="text-center text-sm text-slate-500">
            Ingat kata sandi Anda?
            <a href="{{ route('login') }}" class="font-semibold text-slate-800 hover:underline">Kembali masuk</a>
        </p>
    </div>
</x-guest-layout>
