@extends('layouts.app')

@section('title', 'Circulars & Notice Board — ' . ($school['name'] ?? 'School'))

@section('content')
<div class="space-y-4" x-data="{
    publishModalOpen: false,
    editModalOpen: false,
    editNotice: { id: null, title: '', priority: 'normal', target_audience: 'all', is_pinned: false, content: '', type: 'notice', requires_acknowledgement: false, expires_at: '' },
    openEdit(n) {
        this.editNotice = { id: n.id, title: n.title, priority: n.priority, target_audience: n.target_audience, is_pinned: n.is_pinned, content: n.content, type: n.type ?? 'notice', requires_acknowledgement: n.requires_acknowledgement ?? false, expires_at: n.expires_at ?? '' };
        this.editModalOpen = true;
    }
}">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-1">
        <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center space-x-2">
            <span>Circulars &amp; Notice Board</span>
            @if(($urgentCount ?? 0) > 0)
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-black bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300">
                {{ $urgentCount }} Urgent
            </span>
            @endif
            @if(($pendingCount ?? 0) > 0 && Auth::user()->isAdmin())
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-black bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300">
                {{ $pendingCount }} Pending Approval
            </span>
            @endif
        </h1>

        @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
        <button @click.stop="publishModalOpen = true" type="button"
                class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-xs transition">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            <span>New Circular / Notice</span>
        </button>
        @endif
    </div>

    <!-- Tab Filters -->
    <div class="flex flex-wrap items-center gap-1.5">
        <a href="{{ route('notices.index', ['tab' => 'all']) }}"
           class="px-3 py-1 rounded-lg text-xs font-bold transition {{ !request()->filled('tab') || request()->query('tab') === 'all' ? 'bg-emerald-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
            All ({{ $totalNotices ?? $notices->total() }})
        </a>
        <a href="{{ route('notices.index', ['tab' => 'circular']) }}"
           class="px-3 py-1 rounded-lg text-xs font-bold transition {{ request()->query('tab') === 'circular' ? 'bg-indigo-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
            📋 Circulars
        </a>
        <a href="{{ route('notices.index', ['tab' => 'notice']) }}"
           class="px-3 py-1 rounded-lg text-xs font-bold transition {{ request()->query('tab') === 'notice' ? 'bg-blue-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
            📌 Notices
        </a>
        <a href="{{ route('notices.index', ['tab' => 'urgent']) }}"
           class="px-3 py-1 rounded-lg text-xs font-bold transition {{ request()->query('tab') === 'urgent' ? 'bg-rose-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-rose-600 dark:text-rose-400 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
            🚨 Urgent
        </a>
        @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
        <a href="{{ route('notices.index', ['tab' => 'drafts']) }}"
           class="px-3 py-1 rounded-lg text-xs font-bold transition {{ request()->query('tab') === 'drafts' ? 'bg-slate-700 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
            📝 Drafts
        </a>
        @endif
        @if(Auth::user()->isAdmin())
        <a href="{{ route('notices.index', ['tab' => 'pending']) }}"
           class="px-3 py-1 rounded-lg text-xs font-bold transition {{ request()->query('tab') === 'pending' ? 'bg-amber-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-amber-600 dark:text-amber-400 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
            ⏳ Pending Approval {{ ($pendingCount ?? 0) > 0 ? "({$pendingCount})" : '' }}
        </a>
        @endif
    </div>

    <!-- Notices List -->
    <div class="space-y-3">
        @forelse($notices as $notice)
        @php
            $isRead = in_array($notice->id, $readIds ?? []);
            $isPending = ($notice->status ?? 'published') === 'pending_approval';
            $isDraft = ($notice->status ?? 'published') === 'draft';
            $isCircular = ($notice->type ?? 'notice') === 'circular';
        @endphp
        <div class="bg-white dark:bg-slate-900 rounded-xl p-3.5 sm:p-4 shadow-xs border
            {{ $isPending ? 'border-amber-300 dark:border-amber-700 ring-1 ring-amber-400/20' :
               ($isDraft ? 'border-dashed border-slate-300 dark:border-slate-700' :
               ($notice->priority === 'urgent' ? 'border-rose-300 ring-2 ring-rose-500/10' :
               ($notice->priority === 'high' ? 'border-amber-300' : 'border-slate-200 dark:border-slate-800'))) }} transition">

            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                <div class="flex items-start space-x-3 flex-1 min-w-0">
                    <div class="mt-1 flex-shrink-0">
                        @if($isCircular)
                        <span class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-sm font-black shadow-xs">📋</span>
                        @elseif($notice->priority === 'urgent')
                        <span class="w-8 h-8 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center text-sm font-black shadow-xs">🚨</span>
                        @elseif($notice->priority === 'high')
                        <span class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center text-sm font-black shadow-xs">📢</span>
                        @else
                        <span class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm font-black shadow-xs">📌</span>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-1.5 mb-1">
                            @if($isRead)
                            <span class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">✓ Read</span>
                            @endif
                            @if($notice->is_pinned)
                            <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-slate-900 text-amber-300 border border-slate-700">📌 Pinned</span>
                            @endif
                            @if($isCircular && $notice->circular_number)
                            <span class="px-2 py-0.5 rounded text-[10px] font-black font-mono bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">{{ $notice->circular_number }}</span>
                            @endif
                            @if($isPending)
                            <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-300">⏳ Pending Approval</span>
                            @elseif($isDraft)
                            <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-300 dark:border-slate-700">Draft</span>
                            @else
                            <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider
                                {{ $isCircular ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800' :
                                   ($notice->priority === 'urgent' ? 'bg-rose-100 text-rose-800 border border-rose-300' :
                                   ($notice->priority === 'high' ? 'bg-amber-100 text-amber-800 border border-amber-300' :
                                   'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-700')) }}">
                                {{ $isCircular ? 'Circular' : 'Notice' }}
                            </span>
                            @endif
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold text-slate-500 bg-slate-100 dark:bg-slate-800">{{ ucfirst($notice->target_audience ?? 'all') }}</span>
                            @if($notice->requires_acknowledgement ?? false)
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold text-purple-700 bg-purple-100 dark:bg-purple-950 dark:text-purple-300 border border-purple-200 dark:border-purple-800">Ack. Required</span>
                            @endif
                            <span class="text-[11px] text-slate-400 font-medium">
                                {{ ($notice->published_at ?? $notice->created_at)?->format('M d, Y • h:i A') }}
                            </span>
                        </div>
                        <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white truncate">{{ $notice->title }}</h2>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-1 flex-shrink-0 self-end sm:self-auto">
                    @if($isPending && Auth::user()->isAdmin())
                    <form method="POST" action="{{ route('notices.approve', $notice->id) }}" class="inline">
                        @csrf
                        <button type="submit" class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition" title="Approve & Publish">✓ Approve</button>
                    </form>
                    <form method="POST" action="{{ route('notices.reject', $notice->id) }}" class="inline">
                        @csrf
                        <button type="submit" onclick="return confirm('Reject this circular?')" class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-rose-100 hover:bg-rose-200 text-rose-700 dark:bg-rose-950 dark:text-rose-300 shadow-xs transition" title="Reject">✗ Reject</button>
                    </form>
                    @endif

                    @if(!$isRead && !$isPending && !$isDraft && (($notice->status ?? 'published') === 'published'))
                    <form method="POST" action="{{ route('notices.read', $notice->id) }}" class="inline">
                        @csrf
                        <button type="submit" class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-slate-100 hover:bg-emerald-100 text-slate-600 hover:text-emerald-700 dark:bg-slate-800 dark:hover:bg-emerald-900/40 dark:text-slate-400 dark:hover:text-emerald-300 shadow-xs transition" title="Mark as Read">
                            Mark Read
                        </button>
                    </form>
                    @endif

                    @if(Auth::user()->isAdmin())
                    <a href="{{ route('notices.read-stats', $notice->id) }}" class="px-2 py-1 rounded-lg text-[11px] font-bold text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-slate-800 transition" title="View read stats">📊</a>
                    @endif

                    @if(Auth::user()->isAdmin() || Auth::id() === $notice->author_id)
                    <button type="button"
                            @click="openEdit({{ json_encode(['id' => $notice->id, 'title' => $notice->title, 'priority' => $notice->priority, 'target_audience' => $notice->target_audience, 'is_pinned' => (bool)$notice->is_pinned, 'content' => $notice->content, 'type' => $notice->type ?? 'notice', 'requires_acknowledgement' => (bool)($notice->requires_acknowledgement ?? false), 'expires_at' => $notice->expires_at?->format('Y-m-d') ?? '']) }})"
                            class="p-1 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-slate-800 transition" title="Edit">✏️</button>
                    <form method="POST" action="{{ route('notices.destroy', $notice->id) }}" onsubmit="return confirm('Withdraw this notice?');" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="p-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-slate-800 transition" title="Withdraw">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </form>
                    @endif
                </div>
            </div>

            <!-- Content -->
            <div class="mt-3 text-slate-700 dark:text-slate-300 text-xs sm:text-sm leading-relaxed border-t border-slate-100 dark:border-slate-800 pt-2.5 whitespace-pre-line font-normal">
                {{ $notice->content }}
            </div>

            @if($notice->attachment_url)
            <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800 flex items-center space-x-2">
                <a href="{{ Storage::url($notice->attachment_url) }}" target="_blank"
                   class="inline-flex items-center space-x-2 px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 dark:bg-slate-800 dark:hover:bg-slate-700 text-indigo-700 dark:text-indigo-300 text-xs font-bold transition border border-indigo-200 dark:border-slate-700">
                    <span>📎</span>
                    <span>View Official Document Attachment</span>
                    <span class="text-[10px] text-slate-400">({{ strtoupper(pathinfo($notice->attachment_url, PATHINFO_EXTENSION)) }})</span>
                </a>
            </div>
            @endif

            <!-- Acknowledge button for circulars requiring it -->
            @if(($notice->requires_acknowledgement ?? false) && !$isRead && (($notice->status ?? 'published') === 'published'))
            <div class="mt-2 pt-2 border-t border-dashed border-slate-200 dark:border-slate-800">
                <form method="POST" action="{{ route('notices.read', $notice->id) }}" class="inline">
                    @csrf
                    <input type="hidden" name="acknowledged" value="1">
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-xs transition">
                        ✅ Acknowledge &amp; Confirm Receipt
                    </button>
                </form>
            </div>
            @endif

            <!-- Footer -->
            <div class="mt-3 pt-2.5 border-t border-dashed border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                <div class="flex items-center space-x-2">
                    <span class="w-5 h-5 rounded-full bg-slate-200 dark:bg-slate-800 flex items-center justify-center font-bold text-slate-700 dark:text-slate-300 text-[10px]">
                        {{ substr($notice->author->name ?? 'A', 0, 1) }}
                    </span>
                    <span>{{ $notice->author->name ?? 'Administration' }} ({{ ucfirst($notice->author->role ?? 'admin') }})</span>
                </div>
                <div class="font-mono text-[10px] text-slate-400">
                    @if($isCircular && $notice->circular_number)
                    {{ $notice->circular_number }}
                    @else
                    PA-{{ str_pad($notice->id, 4, '0', STR_PAD_LEFT) }}
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white dark:bg-slate-900 rounded-xl p-6 text-center border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto text-lg mb-2">📭</div>
            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">No Notices Found</h3>
            <span class="text-xs text-slate-400 mt-0.5 inline-block">No notices or circulars have been published yet.</span>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-6">{{ $notices->withQueryString()->links() }}</div>

    <!-- Modal: Publish New Circular / Notice -->
    @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
    <div x-show="publishModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="publishModalOpen = false"></div>
        <div class="relative bg-white dark:bg-slate-900 rounded-xl text-left overflow-hidden shadow-2xl max-w-lg w-full border border-slate-200 dark:border-slate-800 z-10">
            <form method="POST" action="{{ route('notices.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="bg-slate-900 dark:bg-slate-800 px-4 py-3 text-white flex items-center justify-between border-b border-slate-800">
                    <div>
                        <span class="text-[10px] font-black tracking-widest uppercase text-emerald-400">Notice Board</span>
                        <h3 class="text-sm font-extrabold text-white">Publish Circular / Notice</h3>
                    </div>
                    <button type="button" @click="publishModalOpen = false" class="text-slate-400 hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div class="p-4 space-y-3 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Type *</label>
                            <select name="type" required class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-white">
                                <option value="notice">Notice</option>
                                <option value="circular">Circular (numbered)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Status *</label>
                            <select name="status" required class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-white">
                                <option value="published">Publish Now</option>
                                @if(Auth::user()->isTeacher() && !Auth::user()->isAdmin())
                                <option value="pending_approval">Submit for Approval</option>
                                @endif
                                <option value="draft">Save as Draft</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Title / Subject *</label>
                        <input type="text" name="title" required placeholder="e.g. CBSE Pre-Board Exam Schedule 2026"
                               class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-medium text-slate-800 dark:text-white focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Priority *</label>
                            <select name="priority" required class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-white">
                                <option value="normal">Normal</option>
                                <option value="high">High Priority</option>
                                <option value="urgent">🚨 Urgent</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Audience *</label>
                            <select name="target_audience" required class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-white">
                                <option value="all">All</option>
                                <option value="students">Students Only</option>
                                <option value="teachers">Faculty Only</option>
                                <option value="parents">Parents Only</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Content *</label>
                        <textarea name="content" rows="4" required placeholder="Full circular content..."
                                  class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-white focus:ring-2 focus:ring-emerald-500"></textarea>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Expiry Date (optional)</label>
                        <input type="date" name="expires_at" class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Official Document Attachment (optional)</label>
                        <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-slate-100 dark:file:bg-slate-800 file:text-slate-700 dark:file:text-slate-300 hover:file:bg-slate-200">
                        <p class="text-[10px] text-slate-400 mt-0.5">Supports PDF or images up to 10MB.</p>
                    </div>
                    <div class="flex flex-wrap gap-4 pt-1">
                        <label class="flex items-center space-x-2 text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer">
                            <input type="checkbox" name="is_pinned" value="1" class="w-4 h-4 rounded text-emerald-600">
                            <span>📌 Pin to top</span>
                        </label>
                        <label class="flex items-center space-x-2 text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer">
                            <input type="checkbox" name="requires_acknowledgement" value="1" class="w-4 h-4 rounded text-purple-600">
                            <span>✅ Require acknowledgement</span>
                        </label>
                    </div>
                </div>
                <div class="bg-slate-50 dark:bg-slate-800/60 px-4 py-3 flex items-center justify-end space-x-2 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" @click="publishModalOpen = false" class="px-3.5 py-1.5 rounded-lg text-slate-600 dark:text-slate-400 text-xs font-bold">Cancel</button>
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-xs">Publish 📢</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white dark:bg-slate-900 rounded-xl max-w-lg w-full shadow-2xl overflow-hidden border border-slate-200 dark:border-slate-800" @click.away="editModalOpen = false">
            <div class="bg-slate-900 px-4 py-3 flex items-center justify-between text-white border-b border-slate-800">
                <h3 class="text-sm font-extrabold">✏️ Edit Notice / Circular</h3>
                <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form :action="'{{ url('/notices') }}/' + editNotice.id" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="p-4 space-y-3 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Type</label>
                            <select name="type" x-model="editNotice.type" class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-white">
                                <option value="notice">Notice</option>
                                <option value="circular">Circular</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Priority *</label>
                            <select name="priority" x-model="editNotice.priority" required class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-white">
                                <option value="normal">Normal</option>
                                <option value="high">High Priority</option>
                                <option value="urgent">🚨 Urgent</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Title *</label>
                        <input type="text" name="title" x-model="editNotice.title" required class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Audience *</label>
                        <select name="target_audience" x-model="editNotice.target_audience" required class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-white">
                            <option value="all">All</option>
                            <option value="students">Students</option>
                            <option value="teachers">Teachers</option>
                            <option value="parents">Parents</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Content *</label>
                        <textarea name="content" x-model="editNotice.content" rows="4" required class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white leading-relaxed focus:ring-2 focus:ring-emerald-500"></textarea>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Expiry Date</label>
                        <input type="date" name="expires_at" x-model="editNotice.expires_at" class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Replace Attachment (optional)</label>
                        <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-slate-100 dark:file:bg-slate-800 file:text-slate-700 dark:file:text-slate-300 hover:file:bg-slate-200">
                        <p class="text-[10px] text-slate-400 mt-0.5">Upload a new PDF or image to replace current attachment (max 10MB).</p>
                    </div>
                    <div class="flex flex-wrap gap-4 pt-1">
                        <label class="flex items-center space-x-2 text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer">
                            <input type="checkbox" name="is_pinned" value="1" x-model="editNotice.is_pinned" class="w-4 h-4 rounded text-emerald-600">
                            <span>📌 Pin to top</span>
                        </label>
                        <label class="flex items-center space-x-2 text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer">
                            <input type="checkbox" name="requires_acknowledgement" value="1" x-model="editNotice.requires_acknowledgement" class="w-4 h-4 rounded text-purple-600">
                            <span>✅ Require acknowledgement</span>
                        </label>
                    </div>
                </div>
                <div class="bg-slate-50 dark:bg-slate-800/60 px-4 py-3 flex items-center justify-end space-x-2 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" @click="editModalOpen = false" class="px-3.5 py-1.5 rounded-lg text-slate-600 text-xs font-bold">Cancel</button>
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-xs">Update</button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
@endsection
