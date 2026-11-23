@extends('layouts.app')

@section('title', 'Library Management — ' . ($school['name'] ?? 'Infix LMS'))

@section('content')
<div class="max-w-[1600px] mx-auto space-y-5 pb-24 sm:pb-8">

    <!-- Infix Breadcrumbs & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center space-x-2 text-[11px] text-slate-400 font-semibold mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-slate-600 dark:hover:text-slate-200 transition">Dashboard</a>
                <span>/</span>
                <span class="text-slate-500">Library</span>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-bold">Book Catalog &amp; Circulation</span>
            </div>

            <div class="flex items-center space-x-2.5">
                <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm shadow-xs" style="background: var(--system_primary_gradient);">
                    <i class="ti-book"></i>
                </span>
                <h1 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white tracking-tight">
                    Library Management Hub
                </h1>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold text-white shadow-2xs" style="background: var(--system_primary_gradient);">
                    Infix Library
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Catalog academic literature, manage circulation, issue books to students and faculty, and track returns.
            </p>
        </div>

        @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
        <div class="flex items-center flex-wrap gap-2">
            <button type="button" 
                    onclick="document.getElementById('issue-book-modal').classList.remove('hidden')"
                    class="px-3 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold transition shadow-xs flex items-center space-x-1.5">
                <i class="ti-share text-xs"></i>
                <span>Issue Book</span>
            </button>
            <button type="button" 
                    onclick="document.getElementById('add-book-modal').classList.remove('hidden')"
                    class="primary-btn" style="padding: 0.4rem 1.1rem; font-size: 0.75rem;">
                <i class="ti-plus text-xs"></i>
                <span>Add Book</span>
            </button>
        </div>
        @endif
    </div>

    <!-- Infix Summary Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
        <div class="white-box single-summery p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total Catalog Titles</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-1.5 font-mono">{{ $stats['total_titles'] }}</div>
            <p class="text-[10px] text-slate-400 mt-1">Unique textbook editions</p>
        </div>
        <div class="white-box single-summery p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider block">Total Copies</span>
            <div class="text-2xl font-black text-emerald-600 mt-1.5 font-mono">{{ $stats['total_books'] }}</div>
            <p class="text-[10px] text-emerald-600 font-semibold mt-1">Institutional physical copies</p>
        </div>
        <div class="white-box single-summery p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-[11px] font-bold text-blue-600 uppercase tracking-wider block">Available Copies</span>
            <div class="text-2xl font-black text-blue-600 mt-1.5 font-mono">{{ $stats['available_books'] }}</div>
            <p class="text-[10px] text-slate-400 mt-1">Ready for circulation</p>
        </div>
        <div class="white-box single-summery p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-[11px] font-bold text-purple-600 uppercase tracking-wider block">Issued Copies</span>
            <div class="text-2xl font-black text-purple-600 mt-1.5 font-mono">{{ $stats['issued_books'] }}</div>
            <p class="text-[10px] text-slate-400 mt-1">Currently in circulation</p>
        </div>
        <div class="white-box single-summery p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-[11px] font-bold text-rose-600 uppercase tracking-wider block">Overdue Copies</span>
            <div class="text-2xl font-black text-rose-600 mt-1.5 font-mono">{{ $stats['overdue_books'] }}</div>
            <p class="text-[10px] text-slate-400 mt-1">Past due date; no automatic fine</p>
        </div>
    </div>

    <!-- Main Grid: Book Catalog & Circulation History -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <!-- Left: Book Catalog (2 Cols) -->
        <div class="lg:col-span-2 space-y-3">
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4 shadow-2xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 mb-3 pb-2 border-b border-slate-100 dark:border-slate-800">
                    <h2 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                        <span>📚</span> Books Catalog &amp; Rack Locations
                    </h2>

                    <!-- Live Search & Filter Form -->
                    <form method="GET" action="{{ route('school.library.index') }}" class="flex items-center gap-2">
                        <input type="text" name="search" value="{{ $search }}" placeholder="Search title, author, ISBN..." 
                               class="px-2.5 py-1 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        <select name="subject_id" onchange="this.form.submit()" class="px-2.5 py-1 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-900 dark:text-white">
                            <option value="">-- All Subjects --</option>
                            @foreach($subjects as $sub)
                            <option value="{{ $sub->id }}" {{ $subjectId == $sub->id ? 'selected' : '' }}>{{ $sub->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="px-2.5 py-1 rounded-lg bg-slate-900 text-white text-xs font-bold">Search</button>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600 dark:text-slate-400">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase font-black text-[10px] text-slate-500 tracking-wider">
                            <tr>
                                <th class="p-3">Book #</th>
                                <th class="p-3">Title &amp; Author</th>
                                <th class="p-3">Subject</th>
                                <th class="p-3">Rack</th>
                                <th class="p-3">Copies</th>
                                <th class="p-3 text-right">Available</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($books as $b)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                <td class="p-3 font-mono font-bold text-purple-600 dark:text-purple-400">
                                    {{ $b->book_number }}
                                </td>
                                <td class="p-3">
                                    <span class="font-bold text-slate-900 dark:text-white block">{{ $b->book_title }}</span>
                                    <span class="text-[10px] text-slate-400">{{ $b->author_name ?? 'Unknown Author' }} &bull; ISBN: {{ $b->isbn_number ?? 'N/A' }}</span>
                                </td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                        {{ $b->subject?->name ?? 'General' }}
                                    </span>
                                </td>
                                <td class="p-3 font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300">
                                    {{ $b->rack_number ?? 'Rack 01' }}
                                </td>
                                <td class="p-3 font-mono font-bold text-slate-900 dark:text-white">
                                    {{ $b->quantity }}
                                </td>
                                <td class="p-3 text-right font-mono font-black {{ $b->available_quantity > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-500' }}">
                                    {{ $b->available_quantity }}
                                    @if($b->available_quantity <= 0 && (Auth::user()->isStudent() || Auth::user()->role === 'teacher'))
                                    <form method="POST" action="{{ route('school.library.reserve') }}" class="mt-1">
                                        @csrf
                                        <input type="hidden" name="book_id" value="{{ $b->id }}">
                                        <button type="submit" class="text-[9px] underline text-blue-600">Reserve</button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="p-6 text-center text-slate-400">
                                    No books cataloged yet. Click "Add Book" to register literature.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                    {{ $books->links() }}
                </div>
            </div>
        </div>

        <!-- Right: Active Circulation & Returns (1 Col) -->
        <div class="space-y-3">
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4 shadow-2xs">
                <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100 dark:border-slate-800">
                    <h2 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5">
                        <span>🔄</span> Book Circulation
                    </h2>
                    <form method="GET" action="{{ route('school.library.index') }}" class="flex items-center gap-1">
                        <input type="hidden" name="search" value="{{ $search }}">
                        <select name="status" onchange="this.form.submit()" class="text-[10px] rounded border-slate-200 dark:border-slate-700 dark:bg-slate-800">
                            <option value="active" {{ $status === 'active' ? 'selected' : '' }}>All issues</option>
                            <option value="issued" {{ $status === 'issued' ? 'selected' : '' }}>Issued</option>
                            <option value="overdue" {{ $status === 'overdue' ? 'selected' : '' }}>Overdue</option>
                            <option value="returned" {{ $status === 'returned' ? 'selected' : '' }}>Returned</option>
                        </select>
                    </form>
                </div>

                <div class="space-y-2.5">
                    @forelse($issues as $iss)
                    <div class="p-3 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 text-xs space-y-1">
                        <div class="flex items-center justify-between">
                            <strong class="font-bold text-slate-900 dark:text-white truncate">{{ $iss->book?->book_title }}</strong>
                            <span class="px-2 py-0.2 rounded-full text-[9px] font-extrabold uppercase {{ $iss->status === 'returned' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300' }}">
                                {{ $iss->status }}
                            </span>
                        </div>
                        <div class="text-[11px] text-slate-500">
                            Member: <strong class="text-slate-800 dark:text-slate-200">{{ $iss->member?->name }}</strong>
                        </div>
                        <div class="flex items-center justify-between text-[10px] text-slate-400 pt-1">
                            <span>Due: <strong class="text-rose-500">{{ $iss->due_date->format('M d, Y') }}</strong></span>
                            @if($iss->status === 'issued' && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
                            <div class="flex items-center gap-2">
                            <form method="POST" action="{{ route('school.library.renew', $iss->id) }}" class="flex items-center gap-1">
                                @csrf
                                <input type="date" name="due_date" min="{{ now()->addDay()->toDateString() }}" value="{{ $iss->due_date->copy()->addDays(14)->toDateString() }}" required class="w-28 text-[9px] rounded border-slate-200 dark:border-slate-700 dark:bg-slate-900">
                                <button type="submit" class="px-2 py-0.5 rounded bg-blue-600 text-white font-bold text-[10px]">Renew ({{ $iss->renewal_count ?? 0 }})</button>
                            </form>
                            <form method="POST" action="{{ route('school.library.return', $iss->id) }}">
                                @csrf
                                <button type="submit" class="px-2 py-0.5 rounded bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[10px] transition">
                                    Return Book
                                </button>
                            </form>
                            </div>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="p-4 text-center text-xs text-slate-400">
                        No active book circulations recorded.
                    </div>
                    @endforelse
                </div>

                <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                    {{ $issues->links() }}
                </div>
            </div>
        </div>

    </div>

</div>

<!-- Modal: Add Book -->
@if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
<div id="add-book-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs hidden p-4">
    <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full shadow-2xl">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Catalog New Book</h3>
            <button onclick="document.getElementById('add-book-modal').classList.add('hidden')" class="text-slate-400 hover:text-white text-sm">✕</button>
        </div>

        <form method="POST" action="{{ route('school.library.books.store') }}" class="space-y-3 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Book Title *</label>
                <input type="text" name="book_title" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
            </div>

            <div class="grid grid-cols-2 gap-2.5">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Book Number *</label>
                    <input type="text" name="book_number" placeholder="BK-1001" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">ISBN Number</label>
                    <input type="text" name="isbn_number" placeholder="978-3-16-..." class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2.5">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Author Name</label>
                    <input type="text" name="author_name" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Publisher</label>
                    <input type="text" name="publisher_name" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>
            </div>

            <div class="grid grid-cols-3 gap-2">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Subject</label>
                    <select name="subject_id" class="w-full px-2.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                        <option value="">General</option>
                        @foreach($subjects as $sub)
                        <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Rack #</label>
                    <input type="text" name="rack_number" placeholder="Rack A-04" class="w-full px-2.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Quantity *</label>
                    <input type="number" name="quantity" value="1" min="1" required class="w-full px-2.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                </div>
            </div>

            <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="document.getElementById('add-book-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold">Cancel</button>
                <button type="submit" class="primary-btn" style="padding: 0.4rem 1.1rem; font-size: 0.75rem;">Save Book</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Issue Book -->
<div id="issue-book-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs hidden p-4">
    <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full shadow-2xl">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Issue Book to Student/Faculty</h3>
            <button onclick="document.getElementById('issue-book-modal').classList.add('hidden')" class="text-slate-400 hover:text-white text-sm">✕</button>
        </div>

        <form method="POST" action="{{ route('school.library.issue') }}" class="space-y-3 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Select Book *</label>
                <select name="book_id" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                    @foreach($books as $b)
                    <option value="{{ $b->id }}" {{ $b->available_quantity <= 0 ? 'disabled' : '' }}>
                        {{ $b->book_title }} ({{ $b->book_number }}) - Available: {{ $b->available_quantity }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Select Member (Student / Staff) *</label>
                <select name="member_id" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                    @foreach($members as $m)
                    <option value="{{ $m->id }}">{{ $m->name }} ({{ strtoupper($m->role) }}) - {{ $m->student_id_number ?? $m->email }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Return Due Date *</label>
                <input type="date" name="due_date" value="{{ now()->addDays(14)->toDateString() }}" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
            </div>

            <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="document.getElementById('issue-book-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold">Confirm Issue</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
