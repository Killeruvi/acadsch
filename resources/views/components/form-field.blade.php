@props([
    'label' => '',
    'name' => '',
    'type' => 'text',
    'value' => null,
    'required' => false,
    'placeholder' => '',
    'help' => null,
])

<div class="space-y-1.5">
    @if($label)
        <label for="{{ $name }}" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
            {{ $label }}
            @if($required)
                <span class="text-rose-500">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        <input 
            type="{{ $type }}" 
            name="{{ $name }}" 
            id="{{ $name }}" 
            value="{{ old($name, $value) }}"
            placeholder="{{ $placeholder }}"
            {{ $required ? 'required' : '' }}
            {{ $attributes->merge(['class' => 'w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-colors' . ($errors->has($name) ? ' border-rose-500 ring-rose-500/20' : '')]) }}
        />
    </div>

    @if($help)
        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $help }}</p>
    @endif

    @error($name)
        <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold">{{ $message }}</p>
    @enderror
</div>
