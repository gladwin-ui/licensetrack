@props([
    'name',
    'options' => [],
    'selected' => null,
    'placeholder' => 'Pilih...',
    'class' => 'w-full bg-slate-50 border border-slate-200 rounded-2xl px-3.5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-white focus:bg-white focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition shadow-sm',
    'xModel' => null
])
@php
    $formattedOptions = [];
    foreach($options as $val => $lbl) {
        $formattedOptions[] = ['value' => (string)$val, 'label' => $lbl];
    }
@endphp
<div x-data="{
        open: false,
        options: {{ json_encode($formattedOptions) }},
        selectedValue: @if($xModel) {{ $xModel }} @else '{{ $selected }}' @endif,
        get selectedLabel() {
            let opt = this.options.find(o => o.value == this.selectedValue);
            return opt ? opt.label : '{{ $placeholder }}';
        },
        selectOption(val) {
            this.selectedValue = val;
            @if($xModel)
            {{ $xModel }} = val;
            @endif
            this.open = false;
            @if(!$xModel)
            $nextTick(() => {
                let input = $refs.hiddenInput;
                if(input) input.dispatchEvent(new Event('input', { bubbles: true }));
            });
            @endif
        }
    }"
    class="relative"
    @click.away="open = false"
>
    @if(!$xModel)
    <input type="hidden" name="{{ $name }}" x-model="selectedValue" x-ref="hiddenInput">
    @endif

    <button type="button" @click="open = !open" 
            class="flex items-center justify-between text-left {{ $class }}">
        <span x-html="selectedLabel" class="truncate block w-full pr-2"></span>
        <svg class="w-4 h-4 text-slate-400 shrink-0 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div x-show="open" 
         x-transition.opacity.duration.200ms
         x-cloak
         class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-60 overflow-y-auto py-1">
        <template x-for="option in options" :key="option.value">
            <div @click="selectOption(option.value)"
                 class="px-4 py-2 text-sm text-slate-700 hover:bg-red-50 hover:text-red-700 cursor-pointer transition flex items-center justify-between"
                 :class="selectedValue == option.value ? 'bg-red-50 font-semibold text-red-700' : ''">
                <span class="truncate" x-html="option.label"></span>
                <svg x-show="selectedValue == option.value" class="w-4 h-4 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
        </template>
    </div>
</div>