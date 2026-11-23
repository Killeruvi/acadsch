@props([
    'headers' => [],
    'emptyMessage' => 'No records found in this table.',
    'emptyIcon' => '📄',
    'pagination' => null,
])

<div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl overflow-hidden shadow-xs">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
            @if(!empty($headers))
                <thead>
                    <tr class="bg-slate-50/80 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        @foreach($headers as $header)
                            <th class="py-3 px-4">{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
            @endif
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @if(isset($empty) && $empty)
        <div class="py-12 text-center text-slate-400">
            <div class="text-3xl mb-2">{{ $emptyIcon }}</div>
            <p class="text-sm font-medium">{{ $emptyMessage }}</p>
        </div>
    @endif

    @if($pagination)
        <div class="p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/40 dark:bg-slate-900/40">
            {{ $pagination }}
        </div>
    @endif
</div>
