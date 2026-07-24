<x-guest-layout>
    <x-slot name="title">Masuk</x-slot>

    <div x-data="{ loading: false }" class="space-y-6">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900 tracking-tight">Masuk ke akun</h2>
            <p class="text-sm text-slate-400 mt-1">Masukkan email dan kata sandi Anda.</p>
        </div>

        <!-- Session Status -->
        @if (session('status'))
            <div class="text-sm text-emerald-600 bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3">
                {{ session('status') }}
            </div>
        @endif

        @include('auth.partials.error-banner', ['title' => 'Gagal masuk'])

        <form method="POST" action="{{ route('login') }}" @submit="loading = true" class="space-y-4">
            @csrf

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                <input id="email" name="email" type="email" autocomplete="email" required
                       value="{{ old('email') }}" autofocus
                       placeholder="nama@hariff.co.id"
                       class="block w-full px-4 py-2.5 border border-gray-200 rounded-lg text-slate-900 placeholder-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 transition">
            </div>

            <!-- Password -->
            <div x-data="{ show: false }">
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Kata Sandi</label>
                <div class="relative">
                    <input id="password" name="password" :type="show ? 'text' : 'password'"
                           type="password" autocomplete="current-password" required
                           placeholder="Masukkan kata sandi"
                           class="block w-full px-4 py-2.5 pr-11 border border-gray-200 rounded-lg text-slate-900 placeholder-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 transition">
                    <button type="button" @click="show = !show" tabindex="-1"
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-300 hover:text-slate-500 transition"
                            :aria-label="show ? 'Sembunyikan kata sandi' : 'Lihat kata sandi'">
                        <svg x-show="!show" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <svg x-show="show" x-cloak class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Remember & Forgot Password -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <input id="remember_me" name="remember" type="checkbox"
                           class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-500/20 focus:ring-offset-0 transition">
                    <label for="remember_me" class="text-sm text-slate-500 select-none cursor-pointer">Ingat saya</label>
                </div>
                <a href="{{ route('password.request') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition">
                    Lupa password?
                </a>
            </div>

            <!-- Submit Button -->
            <button type="submit" :disabled="loading"
                    class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg text-sm font-semibold text-white bg-slate-900 hover:bg-slate-800 disabled:bg-slate-700 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-slate-700 focus:ring-offset-2 transition-all shadow-sm">
                <svg x-show="loading" x-cloak class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span x-text="loading ? 'Memproses...' : 'Masuk'">Masuk</span>
            </button>
        </form>

        <p class="text-center text-sm text-slate-500">
            Belum punya akun?
            <a href="{{ route('register') }}" class="font-semibold text-slate-800 hover:underline">Daftar</a>
        </p>
    </div>
</x-guest-layout>
