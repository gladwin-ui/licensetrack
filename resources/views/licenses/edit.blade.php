<x-app-layout>
    <x-slot name="title">Edit Lisensi</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('licenses.show', $license) }}" class="text-gray-400 hover:text-gray-600 transition" title="Kembali ke Detail Lisensi">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">Edit lisensi</h1>
                <p class="text-xs text-gray-500 mt-0.5">{{ $license->name }}</p>
            </div>
        </div>
    </x-slot>

    <div class="px-4 sm:px-6 lg:px-8 py-6">
        <div class="max-w-4xl mx-auto">
            <form method="POST" action="{{ route('licenses.update', $license) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                @include('licenses._form', ['license' => $license])

                <div class="flex items-center justify-end gap-3 mt-6">
                    <a href="{{ route('licenses.show', $license) }}"
                       class="px-5 py-2.5 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 active:scale-[0.98] transition-all duration-150 shadow-sm">
                        Batal
                    </a>
                    <button type="submit"
                            class="px-6 py-2.5 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 hover:brightness-110 active:scale-[0.98] transition-all duration-150 shadow-sm">
                        Perbarui Lisensi / Sertifikat
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
