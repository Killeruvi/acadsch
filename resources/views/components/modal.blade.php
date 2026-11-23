@props([
    'id' => 'app-modal',
    'title' => 'Modal Dialog',
    'size' => 'md', // sm, md, lg, xl
])

@php
    $maxWidth = [
        'sm' => 'max-w-md',
        'md' => 'max-w-lg',
        'lg' => 'max-w-2xl',
        'xl' => 'max-w-4xl',
    ][$size] ?? 'max-w-lg';
@endphp

<div id="{{ $id }}" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <!-- Backdrop -->
        <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-xs" onclick="document.getElementById('{{ $id }}').classList.add('hidden')"></div>

        <!-- Modal Panel -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle w-full {{ $maxWidth }} border border-slate-200 dark:border-slate-800">
            <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ $title }}</h3>
                <button type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition" onclick="document.getElementById('{{ $id }}').classList.add('hidden')">
                    <span class="sr-only">Close</span>
                    ✕
                </button>
            </div>
            <div class="p-6">
                {{ $slot }}
            </div>
            @if(isset($footer))
                <div class="px-6 py-4 bg-slate-50/70 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-3">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
</div>
