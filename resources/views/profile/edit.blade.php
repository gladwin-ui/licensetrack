<x-app-layout>
    <x-slot name="title">Profil Saya</x-slot>
    <x-slot name="header">
        <h1 class="font-display text-xl sm:text-2xl font-semibold text-gray-900 tracking-tight leading-snug">Profil Saya</h1>
        <p class="text-sm text-gray-500 mt-1">Ubah informasi akun dan kata sandi Anda</p>
    </x-slot>

    <div class="px-4 sm:px-6 lg:px-8 py-6">
        <div class="max-w-4xl mx-auto space-y-6">
            <div class="p-6 bg-white border border-gray-200/70 rounded-xl shadow-sm">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-6 bg-white border border-gray-200/70 rounded-xl shadow-sm">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
