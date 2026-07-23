{{--
    Toast Component
    Usage from Blade (via flash): handled automatically by toast-container in app.blade.php
    Usage from Alpine JS (client-side):
        $dispatch('toast', { type: 'success', title: 'Berhasil!', message: 'Data tersimpan.' })
        type: 'success' | 'error' | 'warning' | 'info'
        duration (ms, optional, default 5000): $dispatch('toast', { type:'error', title:'Gagal', message:'...', duration: 8000 })
--}}

@props([
    'type'     => 'success',
    'title'    => '',
    'message'  => '',
    'duration' => 5000,
])

@php
    $configs = [
        'success' => [
            'bar'        => 'bg-green-500',
            'icon_bg'    => 'bg-green-50',
            'icon_color' => 'text-green-600',
            'duration'   => $duration,
        ],
        'error' => [
            'bar'        => 'bg-red-500',
            'icon_bg'    => 'bg-red-50',
            'icon_color' => 'text-red-600',
            'duration'   => max($duration, 7000), // errors stay longer
        ],
        'warning' => [
            'bar'        => 'bg-orange-500',
            'icon_bg'    => 'bg-orange-50',
            'icon_color' => 'text-orange-600',
            'duration'   => $duration,
        ],
        'info' => [
            'bar'        => 'bg-red-500',
            'icon_bg'    => 'bg-red-50',
            'icon_color' => 'text-red-600',
            'duration'   => $duration,
        ],
    ];
    $cfg = $configs[$type] ?? $configs['info'];
@endphp

<div
    x-data="{
        visible: true,
        paused: false,
        dismiss() { this.visible = false; }
    }"
    x-show="visible"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-x-8 scale-95"
    x-transition:enter-end="opacity-100 translate-x-0 scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-x-0 scale-100"
    x-transition:leave-end="opacity-0 translate-x-8"
    x-init="setTimeout(() => dismiss(), {{ $cfg['duration'] }})"
    @mouseenter="paused = true"
    @mouseleave="paused = false"
    :class="paused ? 'toast-paused' : ''"
    class="w-80 bg-white rounded-xl shadow-lg border border-gray-200/70 overflow-hidden relative flex items-start gap-3 p-4 pointer-events-auto"
    role="status"
    x-cloak
>
    {{-- Coloured icon badge --}}
    <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center {{ $cfg['icon_bg'] }} {{ $cfg['icon_color'] }}">
        @if ($type === 'success')
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        @elseif ($type === 'error')
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        @elseif ($type === 'warning')
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
        @else
            {{-- info --}}
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20A10 10 0 0012 2z"/></svg>
        @endif
    </div>

    {{-- Content --}}
    <div class="flex-1 min-w-0 pt-0.5">
        @if ($title)
            <p class="text-sm font-semibold text-gray-900 leading-tight">{{ $title }}</p>
        @endif
        @if ($message)
            <p class="text-xs text-gray-500 mt-0.5 leading-relaxed whitespace-pre-wrap">{{ $message }}</p>
        @endif
    </div>

    {{-- Close button --}}
    <button
        type="button"
        @click="dismiss()"
        aria-label="Tutup notifikasi"
        class="flex-shrink-0 text-gray-400 hover:text-gray-600 transition -mt-0.5 -mr-0.5 p-0.5 rounded"
    >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>

    {{-- Progress bar --}}
    <div class="absolute bottom-0 left-0 right-0 h-1 bg-gray-100">
        <div
            class="h-full toast-bar {{ $cfg['bar'] }}"
            style="--toast-duration: {{ $cfg['duration'] }}ms;"
        ></div>
    </div>
</div>
