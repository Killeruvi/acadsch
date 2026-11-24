@extends('layouts.app')

@section('title', 'Institutional Notifications' . ' — ' . ($school['name'] ?? config('app.name', 'School ERP')))

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header Section -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-indigo-900/50 flex flex-col sm:flex-row sm:items-center justify-between gap-6">
        <div>
            <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-500/20 text-indigo-300 border border-indigo-400/30 mb-3">
                <span>🔔 Instant Notification Hub</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
                Notifications & Academic Alerts
            </h1>
            <p class="text-sm text-slate-300 mt-2 max-w-xl leading-relaxed">
                Stay updated with real-time alerts across Bloom's Taxonomy evaluations, daily DPP checks, fee invoices, homework tasks, and attendance logs.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if($unreadCount > 0)
            <form method="POST" action="{{ route('notifications.mark-all-read') }}">
                @csrf
                <button type="submit" class="px-3.5 py-2 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs font-bold border border-white/20 backdrop-blur-md transition flex items-center space-x-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span>Mark All as Read ({{ $unreadCount }})</span>
                </button>
            </form>
            @endif

            @if(isset($readCount) && $readCount > 0)
            <form method="POST" action="{{ route('notifications.delete-read') }}" onsubmit="return confirm('Are you sure you want to delete all {{ $readCount }} read notification(s)?');">
                @csrf
                <button type="submit" class="px-3.5 py-2 bg-rose-500/20 hover:bg-rose-500/30 text-rose-200 rounded-xl text-xs font-bold border border-rose-400/30 backdrop-blur-md transition flex items-center space-x-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    <span>Delete All Read ({{ $readCount }})</span>
                </button>
            </form>
            @endif
        </div>
    </div>

    <!-- Filter Pills -->
    @php
        $currentType = request('type', 'all');
        $currentFilter = request('filter');
    @endphp
    <div class="flex items-center space-x-2 overflow-x-auto pb-2 scrollbar-thin">
        <a href="{{ route('notifications.index', ['type' => 'all']) }}" 
           class="px-4 py-2 rounded-xl text-xs font-bold transition flex-shrink-0 {{ $currentType === 'all' && !$currentFilter ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
            All Notifications
        </a>
        <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" 
           class="px-4 py-2 rounded-xl text-xs font-bold transition flex-shrink-0 {{ $currentFilter === 'unread' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
            Unread Only ({{ $unreadCount }})
        </a>
        <a href="{{ route('notifications.index', ['type' => 'bloom']) }}" 
           class="px-4 py-2 rounded-xl text-xs font-bold transition flex-shrink-0 {{ in_array($currentType, ['bloom', 'blooms']) ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
            🧠 Bloom's Taxonomy
        </a>
        <a href="{{ route('notifications.index', ['type' => 'dpp']) }}" 
           class="px-4 py-2 rounded-xl text-xs font-bold transition flex-shrink-0 {{ $currentType === 'dpp' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
            🎯 Daily DPPs
        </a>
        <a href="{{ route('notifications.index', ['type' => 'homework']) }}" 
           class="px-4 py-2 rounded-xl text-xs font-bold transition flex-shrink-0 {{ $currentType === 'homework' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
            📚 Homework
        </a>
        <a href="{{ route('notifications.index', ['type' => 'fee']) }}" 
           class="px-4 py-2 rounded-xl text-xs font-bold transition flex-shrink-0 {{ in_array($currentType, ['fee', 'fees']) ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
            💰 Fees & Invoices
        </a>
        <a href="{{ route('notifications.index', ['type' => 'attendance']) }}" 
           class="px-4 py-2 rounded-xl text-xs font-bold transition flex-shrink-0 {{ $currentType === 'attendance' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
            📋 Attendance
        </a>
        <a href="{{ route('notifications.index', ['type' => 'assignment']) }}" 
           class="px-4 py-2 rounded-xl text-xs font-bold transition flex-shrink-0 {{ $currentType === 'assignment' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
            📝 Assignments
        </a>
    </div>

    <!-- Notifications List Container -->
    <div x-data="{
        selected: [],
        allIds: {{ json_encode($notifications->pluck('id')) }},
        toggleAll() {
            if (this.selected.length === this.allIds.length) {
                this.selected = [];
            } else {
                this.selected = [...this.allIds];
            }
        }
    }" class="space-y-3">

        @if($notifications->isNotEmpty())
        <!-- Selection & Bulk Action Bar -->
        <div class="flex items-center justify-between px-4 py-2.5 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 text-xs shadow-xs">
            <label class="inline-flex items-center space-x-2 cursor-pointer font-bold text-slate-700 dark:text-slate-300 select-none">
                <input type="checkbox" 
                       :checked="selected.length === allIds.length && allIds.length > 0"
                       @change="toggleAll()"
                       class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer">
                <span>Select All (Page {{ $notifications->currentPage() }})</span>
            </label>

            <div class="flex items-center space-x-3" x-show="selected.length > 0" x-cloak>
                <span class="text-slate-500 font-semibold" x-text="selected.length + ' selected'"></span>
                <form method="POST" action="{{ route('notifications.bulk-delete') }}" 
                      @submit="if(!confirm('Permanently delete ' + selected.length + ' selected notification(s)?')) $event.preventDefault();">
                    @csrf
                    <template x-for="id in selected" :key="id">
                        <input type="hidden" name="ids[]" :value="id">
                    </template>
                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs inline-flex items-center space-x-1.5 shadow-xs transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        <span>Delete Selected</span>
                    </button>
                </form>
            </div>
        </div>
        @endif

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden divide-y divide-slate-100">
            @forelse($notifications as $notif)
            <div class="p-4 sm:p-5 hover:bg-slate-50/80 transition flex items-start gap-3 sm:gap-4 {{ !$notif->is_read ? 'bg-indigo-50/30' : '' }}">
                <!-- Selection Checkbox -->
                <div class="pt-2">
                    <input type="checkbox" :value="{{ $notif->id }}" x-model="selected" 
                           class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer">
                </div>

                <!-- Icon -->
                <div class="w-10 h-10 rounded-2xl flex-shrink-0 flex items-center justify-center text-lg {{ !$notif->is_read ? 'bg-indigo-100 text-indigo-700 shadow-sm shadow-indigo-200' : 'bg-slate-100 text-slate-500' }}">
                    {{ $notif->icon ?? '🔔' }}
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0 space-y-1">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center space-x-2">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">
                                {{ $notif->type }}
                            </span>
                            @if(!$notif->is_read)
                                <span class="inline-block w-2 h-2 rounded-full bg-indigo-600 ring-2 ring-indigo-200"></span>
                                <span class="text-[10px] font-extrabold text-indigo-600 uppercase">New</span>
                            @else
                                <span class="text-[10px] font-bold text-slate-400 uppercase">Read</span>
                            @endif
                        </div>
                        <span class="text-[11px] text-slate-400 font-medium">
                            {{ $notif->created_at->diffForHumans() }}
                        </span>
                    </div>

                    <h4 class="font-bold text-slate-900 text-sm sm:text-base">
                        {{ $notif->title }}
                    </h4>

                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        {{ $notif->message }}
                    </p>

                    @if($notif->action_url)
                    <div class="pt-2">
                        <a href="{{ route('notifications.read', $notif->id) }}" 
                           class="inline-flex items-center space-x-1.5 text-xs font-bold text-indigo-600 hover:text-indigo-800 transition">
                            <span>View Details</span>
                            <span>&rarr;</span>
                        </a>
                    </div>
                    @endif
                </div>

                <!-- One-by-One Delete Action -->
                <div class="flex-shrink-0 pt-1">
                    <form method="POST" action="{{ route('notifications.destroy', $notif->id) }}" 
                          onsubmit="return confirm('Delete this notification?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition" 
                                title="Delete this notification">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </form>
                </div>
            </div>
            @empty
            <div class="p-12 text-center text-slate-400 space-y-3">
                <div class="text-4xl">🔔</div>
                <p class="font-semibold text-slate-600">No notifications found.</p>
                <p class="text-xs text-slate-400">You are all caught up! Academic alerts, DPP evaluations, and fee receipts will appear here.</p>
            </div>
            @endforelse
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $notifications->links() }}
    </div>

</div>
@endsection
