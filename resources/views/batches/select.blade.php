@extends('layouts.app')

@section('title', 'Select Class & Batch' . ' — ' . ($school['name'] ?? 'PrimeArc Public School'))

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-3 sm:py-4" 
     x-data="{ 
        showCreateModal: false, 
        showEditModal: false,
        showRequestModal: false, 
        requestedBatchId: null, 
        requestedBatchName: '',
        editBatch: { id: null, class_id: '', name: '', shift: 'morning', timing_slot: '', description: '' },
        openEdit(b) {
            this.editBatch = { id: b.id, class_id: b.class_id, name: b.name, shift: b.shift, timing_slot: b.timing_slot, description: b.description || '' };
            this.showEditModal = true;
        }
     }">

    @if(session('success'))
    <div class="mb-4 p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center justify-between shadow-xs">
        <div class="flex items-center space-x-2">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
        <button type="button" @click="$el.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400">&times;</button>
    </div>
    @endif

    @if(session('error'))
    <div class="mb-4 p-3.5 rounded-xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-xs font-semibold flex items-center justify-between shadow-xs">
        <div class="flex items-center space-x-2">
            <span>⚠️</span>
            <span>{{ session('error') }}</span>
        </div>
        <button type="button" @click="$el.parentElement.remove()" class="text-rose-600 hover:text-rose-800 dark:text-rose-400">&times;</button>
    </div>
    @endif

    <!-- Student Pending Batch Change Request Banner -->
    @if(Auth::user()->isStudent() && $pendingRequest)
    <div class="mb-4 p-3.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-800 text-amber-900 dark:text-amber-200 text-xs font-medium flex items-center justify-between shadow-xs">
        <div class="flex items-center space-x-2">
            <span class="text-base">⏳</span>
            <span>
                <strong>Batch Change Requested:</strong> You have requested to switch to <strong>{{ $pendingRequest->requestedBatch?->name }}</strong>. 
                Status: <span class="uppercase font-bold text-amber-700 dark:text-amber-300">Pending Review</span> by faculty.
            </span>
        </div>
    </div>
    @endif

    <!-- Faculty/Admin Pending Requests Review Station -->
    @if((Auth::user()->isTeacher() || Auth::user()->isAdmin()) && $allPendingRequests->isNotEmpty())
    <div class="mb-5 bg-white dark:bg-slate-900 border border-amber-200 dark:border-amber-900/60 rounded-2xl p-4 shadow-xs">
        <div class="flex items-center justify-between mb-3 pb-2 border-b border-amber-100 dark:border-amber-900/40">
            <h3 class="text-xs font-black uppercase tracking-wider text-amber-700 dark:text-amber-400 flex items-center space-x-1.5">
                <span>📋</span>
                <span>Pending Student Batch Change Requests ({{ $allPendingRequests->count() }})</span>
            </h3>
            <span class="text-[10px] text-slate-400">Teacher &amp; Admin Review</span>
        </div>

        <div class="space-y-2">
            @foreach($allPendingRequests as $req)
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-2.5 rounded-xl bg-amber-50/50 dark:bg-amber-950/20 border border-amber-200/60 dark:border-amber-900/40 text-xs">
                <div>
                    <span class="font-bold text-slate-900 dark:text-white">{{ $req->student?->name }}</span>
                    <span class="text-slate-400 dark:text-slate-500 text-[11px]">&bull; Current: {{ $req->currentBatch?->name ?? 'None' }} &rarr; Target: <strong>{{ $req->requestedBatch?->name }}</strong></span>
                    <p class="text-[11px] text-slate-600 dark:text-slate-300 italic mt-0.5">"{{ $req->reason }}"</p>
                </div>

                <div class="flex items-center space-x-1.5 flex-shrink-0">
                    <form action="{{ route('batches.requests.review', $req->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold transition shadow-xs">
                            ✓ Approve
                        </button>
                    </form>
                    <form action="{{ route('batches.requests.review', $req->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="px-2.5 py-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-bold transition">
                            ✕ Reject
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>

        @if($allPendingRequests instanceof \Illuminate\Pagination\LengthAwarePaginator && $allPendingRequests->hasPages())
        <div class="mt-3 pt-2 border-t border-amber-100 dark:border-amber-900/40">
            {{ $allPendingRequests->links() }}
        </div>
        @endif
    </div>
    @endif

    <!-- Clean, compact header -->
    <div class="mb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <div class="inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 mb-1">
                <span>🎓</span>
                <span>Academic Batches</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">Select Your Class &amp; Batch</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Grade level and academic batches for curriculum subjects and study materials.
            </p>
        </div>

        <!-- Teachers & Admins Only Action Buttons -->
        @if(Auth::user()->isTeacher() || Auth::user()->isAdmin())
        <div class="flex items-center space-x-2">
            <button type="button" 
                    @click="showCreateModal = true"
                    class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition flex items-center space-x-1 shadow-xs">
                <span>+</span>
                <span>Create Custom Batch</span>
            </button>
        </div>
        @endif
    </div>

    <!-- STEP 1: SELECT CLASS -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 mb-4 shadow-xs">
        <div class="flex items-center justify-between mb-2.5">
            <h2 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center space-x-1.5">
                <span class="w-4 h-4 rounded-full bg-emerald-600 text-white text-[10px] flex items-center justify-center font-bold">1</span>
                <span>Academic Grade / Class</span>
            </h2>
            <span class="text-[10px] text-slate-400 dark:text-slate-500">{{ $classes->count() }} Classes</span>
        </div>

        @if(Auth::user()->isStudent())
        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950/50 border border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <div class="flex items-center space-x-2.5">
                <span class="px-2.5 py-1 rounded-lg text-xs font-black bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                    {{ $selectedClass->display_name ?? 'Class 10' }}
                </span>
                <span class="text-xs text-slate-600 dark:text-slate-400 font-medium">
                    Your institutional enrollment is locked to {{ $selectedClass->display_name ?? 'your class' }}. You can request batch changes below.
                </span>
            </div>
            <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Verified Enrollment</span>
        </div>
        @else
        <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-6 lg:grid-cols-12 gap-1.5">
            @foreach($classes as $c)
            <a href="{{ route('batches.select', ['class_id' => $c->id]) }}"
               class="p-2 text-center rounded-xl border transition {{ $selectedClass && $selectedClass->id === $c->id ? 'border-emerald-600 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-900 dark:text-emerald-200 ring-1 ring-emerald-500/20 font-bold' : 'border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 text-slate-700 dark:text-slate-300 hover:border-emerald-300 dark:hover:border-slate-700' }}">
                <div class="text-xs font-black">{{ $c->display_name }}</div>
                <div class="text-[9px] text-slate-400 dark:text-slate-500 mt-0.5">{{ $c->batches_count ?? 0 }} {{ Str::plural('batch', (int)($c->batches_count ?? 0)) }}</div>
            </a>
            @endforeach
        </div>
        @endif
    </div>

    <!-- STEP 2: AVAILABLE BATCHES -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
        <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100 dark:border-slate-800">
            <div>
                <h2 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center space-x-1.5">
                    <span class="w-4 h-4 rounded-full bg-emerald-600 text-white text-[10px] flex items-center justify-center font-bold">2</span>
                    <span>Batches for {{ $selectedClass->display_name ?? 'Selected Class' }}</span>
                </h2>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Registered academic batches.</p>
            </div>
            @if(Auth::user()->isStudent())
            <span class="text-[10px] text-slate-400 dark:text-slate-500">Batch changes require faculty review</span>
            @endif
        </div>

        @if($batches->isEmpty())
        <div class="p-6 text-center rounded-xl bg-slate-50 dark:bg-slate-950/50 border border-dashed border-slate-200 dark:border-slate-800">
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-2">No batches configured yet for {{ $selectedClass->display_name ?? 'this class' }}.</p>
            @if(Auth::user()->isTeacher() || Auth::user()->isAdmin())
            <button type="button" @click="showCreateModal = true" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition shadow-xs">
                + Create First Batch
            </button>
            @endif
        </div>
        @else
        <!-- RECTANGULAR, ONE IN A LINE BATCHES LIST (NOT SQUARED) -->
        <div class="space-y-2.5">
            @foreach($batches as $b)
            @php
                $isCurrent = ($currentBatchId === $b->id);
                $isRequested = ($pendingRequest && $pendingRequest->requested_batch_id === $b->id);
            @endphp
            <div class="border rounded-xl p-3.5 sm:p-4 transition flex flex-col sm:flex-row sm:items-center justify-between gap-3 {{ $isCurrent ? 'border-emerald-500 bg-emerald-50/40 dark:bg-emerald-950/20 ring-1 ring-emerald-500/30' : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:border-slate-300 dark:hover:border-slate-700' }}">
                <div class="flex items-center space-x-3.5 min-w-0">
                    <!-- Shift Icon Badge -->
                    <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl flex items-center justify-center text-lg shrink-0 {{ $b->shift === 'morning' ? 'bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300' : ($b->shift === 'evening' ? 'bg-indigo-100 dark:bg-indigo-950 text-indigo-800 dark:text-indigo-300' : 'bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300') }}">
                        {{ $b->shift === 'morning' ? '☀️' : ($b->shift === 'evening' ? '🌙' : '⏱️') }}
                    </div>

                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-0.5">
                            <span class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-md text-[10px] font-bold {{ $b->shift === 'morning' ? 'bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300' : ($b->shift === 'evening' ? 'bg-indigo-100 dark:bg-indigo-950 text-indigo-800 dark:text-indigo-300' : 'bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300') }}">
                                <span>{{ $b->shift === 'morning' ? '☀️ Morning' : ($b->shift === 'evening' ? '🌙 Evening' : '⏱️ Custom Shift') }}</span>
                            </span>
                            <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                                🕒 {{ $b->timing_slot ?? 'Flexible Timings' }}
                            </span>
                            @if($isCurrent)
                            <span class="text-[9px] font-black uppercase text-emerald-600 dark:text-emerald-400 tracking-wider">
                                ● Assigned
                            </span>
                            @elseif($isRequested)
                            <span class="text-[9px] font-black uppercase text-amber-600 dark:text-amber-400 tracking-wider">
                                ⏳ Pending
                            </span>
                            @endif
                        </div>

                        <h3 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white leading-snug truncate">
                            {{ $b->name }}
                        </h3>
                    </div>
                </div>

                <!-- Action Button based on User Role -->
                <div class="flex items-center justify-between sm:justify-end space-x-3 shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-100 dark:border-slate-800">
                    <span class="text-xs text-slate-400 dark:text-slate-500">
                        👥 {{ $b->students_count ?? 0 }} {{ Str::plural('Student', (int)($b->students_count ?? 0)) }}
                    </span>

                    @if($isCurrent)
                    <div class="flex items-center space-x-1.5">
                        <a href="{{ route('batches.subjects', ['batch' => $b->id]) }}"
                           class="px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition shadow-xs flex items-center space-x-1">
                            <span>Open Subjects</span>
                            <span>&rarr;</span>
                        </a>
                        @if(Auth::user()->isTeacher() || Auth::user()->isAdmin())
                        <button type="button" 
                                @click="openEdit({{ json_encode([
                                    'id' => $b->id,
                                    'class_id' => $b->class_id,
                                    'name' => $b->name,
                                    'shift' => $b->shift,
                                    'timing_slot' => $b->timing_slot,
                                    'description' => $b->description,
                                ]) }})"
                                class="px-2 py-1.5 rounded-xl text-xs font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 border border-indigo-200 dark:border-indigo-800 transition"
                                title="Edit Batch">
                            ✏️
                        </button>
                        <form action="{{ route('batches.destroy', $b->id) }}" method="POST" class="inline" onsubmit="return confirm('CAUTION: Are you sure you want to delete this batch? All enrolled students will be unassigned.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="px-2 py-1.5 rounded-xl text-xs font-bold bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 hover:bg-rose-100 border border-rose-200 dark:border-rose-800 transition"
                                    title="Delete Batch">
                                🗑️
                            </button>
                        </form>
                        @endif
                    </div>
                    @elseif(Auth::user()->isStudent())
                        @if($isRequested)
                        <span class="px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300">
                            Requested ⏳
                        </span>
                        @else
                        <button type="button" 
                                @click="requestedBatchId = {{ $b->id }}; requestedBatchName = '{{ addslashes($b->name) }}'; showRequestModal = true"
                                class="px-3 py-1.5 rounded-xl text-xs font-bold border border-slate-300 dark:border-slate-700 hover:border-amber-500 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 transition">
                            Request Change 📝
                        </button>
                        @endif
                    @else
                    <!-- Teachers and Admins can switch directly or manage -->
                    <div class="flex items-center space-x-1.5">
                        <form action="{{ route('batches.select.store') }}" method="POST" class="inline">
                            @csrf
                            <input type="hidden" name="batch_id" value="{{ $b->id }}">
                            <button type="submit"
                                    class="px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-900 hover:bg-slate-800 dark:bg-slate-800 dark:hover:bg-slate-700 text-white transition shadow-xs flex items-center space-x-1">
                                <span>Select</span>
                                <span>&rarr;</span>
                            </button>
                        </form>
                        <button type="button" 
                                @click="openEdit({{ json_encode([
                                    'id' => $b->id,
                                    'class_id' => $b->class_id,
                                    'name' => $b->name,
                                    'shift' => $b->shift,
                                    'timing_slot' => $b->timing_slot,
                                    'description' => $b->description,
                                ]) }})"
                                class="px-2 py-1.5 rounded-xl text-xs font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 border border-indigo-200 dark:border-indigo-800 transition"
                                title="Edit Batch">
                            ✏️
                        </button>
                        <form action="{{ route('batches.destroy', $b->id) }}" method="POST" class="inline" onsubmit="return confirm('CAUTION: Are you sure you want to delete this batch? All enrolled students will be unassigned.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="px-2 py-1.5 rounded-xl text-xs font-bold bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 hover:bg-rose-100 border border-rose-200 dark:border-rose-800 transition"
                                    title="Delete Batch">
                                🗑️
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <!-- STUDENT MODAL: REQUEST BATCH CHANGE -->
    <div x-show="showRequestModal" 
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
         @keydown.escape.window="showRequestModal = false">
        
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full p-5 shadow-2xl relative"
             @click.away="showRequestModal = false">
            
            <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white flex items-center space-x-1.5">
                    <span>📩</span>
                    <span>Request Batch Change</span>
                </h3>
                <button type="button" @click="showRequestModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg leading-none">&times;</button>
            </div>

            <form action="{{ route('batches.request-change') }}" method="POST" class="space-y-3">
                @csrf
                <input type="hidden" name="requested_batch_id" :value="requestedBatchId">

                <div>
                    <label class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Target Batch</label>
                    <div x-text="requestedBatchName" class="w-full px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 text-xs font-bold"></div>
                </div>

                <div>
                    <label for="change_reason" class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Reason for Request <span class="text-rose-500">*</span></label>
                    <textarea id="change_reason" name="reason" rows="3" required placeholder="Please explain why you need to switch timing (e.g. school schedule conflict, morning tuition timing)..." class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"></textarea>
                </div>

                <div class="pt-2 flex items-center justify-end space-x-2">
                    <button type="button" @click="showRequestModal = false" class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition">Cancel</button>
                    <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition shadow-xs">Submit Request</button>
                </div>
            </form>
        </div>
    </div>

    <!-- TEACHER/ADMIN MODAL: CREATE CUSTOM BATCH -->
    @if(Auth::user()->isTeacher() || Auth::user()->isAdmin())
    <div x-show="showCreateModal" 
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
         @keydown.escape.window="showCreateModal = false">
        
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full p-5 shadow-2xl relative"
             @click.away="showCreateModal = false">
            
            <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white flex items-center space-x-1.5">
                    <span>✨</span>
                    <span>Create New Batch</span>
                </h3>
                <button type="button" @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg leading-none">&times;</button>
            </div>

            <form action="{{ route('batches.create') }}" method="POST" class="space-y-3">
                @csrf
                <input type="hidden" name="class_id" value="{{ $selectedClass->id }}">

                <div>
                    <label class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Target Class</label>
                    <input type="text" disabled value="{{ $selectedClass->display_name ?? $selectedClass->name }}" class="w-full px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold border-0 cursor-not-allowed">
                </div>

                <div>
                    <label for="batch_name" class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Batch Name <span class="text-rose-500">*</span></label>
                    <input type="text" id="batch_name" name="name" required placeholder="e.g. Class 10 - Early Birds Batch" class="w-full px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                </div>

                <div>
                    <label for="batch_shift" class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Timing Shift <span class="text-rose-500">*</span></label>
                    <select id="batch_shift" name="shift" required class="w-full px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                        <option value="morning">☀️ Morning Batch</option>
                        <option value="evening">🌙 Evening / Afternoon Batch</option>
                        <option value="custom">⏱️ Custom Batch</option>
                        <option value="weekend">📅 Weekend Batch</option>
                    </select>
                </div>

                <div>
                    <label for="timing_slot" class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Custom Timing Label</label>
                    <input type="text" id="timing_slot" name="timing_slot" placeholder="e.g. 06:00 AM - 08:30 AM" class="w-full px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                </div>

                <div class="pt-2 flex items-center justify-end space-x-2">
                    <button type="button" @click="showCreateModal = false" class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition">Cancel</button>
                    <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition shadow-xs">Create Batch</button>
                </div>
            </form>
        </div>
    </div>

    <!-- TEACHER / ADMIN MODAL: EDIT BATCH -->
    <div x-show="showEditModal" 
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
         @keydown.escape.window="showEditModal = false">
        
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full p-5 shadow-2xl relative"
             @click.away="showEditModal = false">
            
            <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white flex items-center space-x-1.5">
                    <span>✏️</span>
                    <span>Edit Batch Settings</span>
                </h3>
                <button type="button" @click="showEditModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg leading-none">&times;</button>
            </div>

            <form :action="'{{ url('/my-batch') }}/' + editBatch.id" method="POST" class="space-y-3">
                @csrf
                @method('PUT')
                <input type="hidden" name="class_id" :value="editBatch.class_id">

                <div>
                    <label class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Batch Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" x-model="editBatch.name" required class="w-full px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-hidden font-bold">
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Timing Shift <span class="text-rose-500">*</span></label>
                    <select name="shift" x-model="editBatch.shift" required class="w-full px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                        <option value="morning">☀️ Morning Batch</option>
                        <option value="evening">🌙 Evening / Afternoon Batch</option>
                        <option value="custom">⏱️ Custom Batch</option>
                        <option value="weekend">📅 Weekend Batch</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Timing Slot Label</label>
                    <input type="text" name="timing_slot" x-model="editBatch.timing_slot" placeholder="e.g. 06:00 AM - 08:30 AM" class="w-full px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Description</label>
                    <textarea name="description" x-model="editBatch.description" rows="2" class="w-full px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"></textarea>
                </div>

                <div class="pt-2 flex items-center justify-end space-x-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showEditModal = false" class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition">Cancel</button>
                    <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition shadow-xs">Update Batch</button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
@endsection
