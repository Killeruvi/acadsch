@extends('layouts.app')

@section('title', 'Front Office & Reception — ' . ($school['name'] ?? 'Infix LMS'))

@section('content')
<div class="max-w-[1600px] mx-auto space-y-5 pb-24 sm:pb-8">

    <!-- Infix Breadcrumbs & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center space-x-2 text-[11px] text-slate-400 font-semibold mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-slate-600 dark:hover:text-slate-200 transition">Dashboard</a>
                <span>/</span>
                <span class="text-slate-500">Administration</span>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-bold">Front Office</span>
            </div>

            <div class="flex items-center space-x-2.5">
                <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm shadow-xs" style="background: var(--system_primary_gradient);">
                    <i class="ti-clipboard"></i>
                </span>
                <h1 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white tracking-tight">
                    Front Office &amp; Reception Desk
                </h1>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold text-white shadow-2xs" style="background: var(--system_primary_gradient);">
                    Infix Front Desk
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Visitor pass management, telephone call logs, inbound/outbound postal dispatch, and reception administration.
            </p>
        </div>

        @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
        <div class="flex items-center flex-wrap gap-2">
            <button type="button" 
                    onclick="document.getElementById('add-call-modal').classList.remove('hidden')"
                    class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold transition shadow-xs flex items-center space-x-1.5">
                <i class="ti-headphone-alt text-xs"></i>
                <span>Log Call</span>
            </button>
            <button type="button" 
                    onclick="document.getElementById('add-postal-modal').classList.remove('hidden')"
                    class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold transition shadow-xs flex items-center space-x-1.5">
                <i class="ti-email text-xs"></i>
                <span>Postal Dispatch</span>
            </button>
            <button type="button" 
                    onclick="document.getElementById('add-visitor-modal').classList.remove('hidden')"
                    class="primary-btn" style="padding: 0.4rem 1.1rem; font-size: 0.75rem;">
                <i class="ti-user text-xs"></i>
                <span>Visitor Pass</span>
            </button>
        </div>
        @endif
    </div>

    <!-- Infix Summary Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
        <div class="white-box single-summery p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Today's Visitors</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-1.5 font-mono">{{ $stats['total_visitors_today'] }}</div>
            <p class="text-[10px] text-slate-400 mt-1">Checked in visitors</p>
        </div>
        <div class="white-box single-summery p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-[11px] font-bold text-blue-600 uppercase tracking-wider block">Today's Phone Calls</span>
            <div class="text-2xl font-black text-blue-600 mt-1.5 font-mono">{{ $stats['total_calls_today'] }}</div>
            <p class="text-[10px] text-slate-400 mt-1">Logged telephone calls</p>
        </div>
        <div class="white-box single-summery p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-[11px] font-bold text-purple-600 uppercase tracking-wider block">Postal Dispatches</span>
            <div class="text-2xl font-black text-purple-600 mt-1.5 font-mono">{{ $stats['total_postals'] }}</div>
            <p class="text-[10px] text-slate-400 mt-1">Inbound / outbound mail</p>
        </div>
    </div>

    <!-- Front Office Tabs & Tables -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4 shadow-2xs space-y-4">
        <div class="flex items-center space-x-2 border-b border-slate-100 dark:border-slate-800 pb-2">
            <h2 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5">
                <span>📋</span> Campus Visitor Register
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-400">
                <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase font-black text-[10px] text-slate-500 tracking-wider">
                    <tr>
                        <th class="p-3">Visitor Name</th>
                        <th class="p-3">Purpose</th>
                        <th class="p-3">Person to Meet</th>
                        <th class="p-3">In Time</th>
                        <th class="p-3">Date</th>
                        <th class="p-3">Phone</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($visitors as $v)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                        <td class="p-3 font-bold text-slate-900 dark:text-white">
                            {{ $v->name }}
                        </td>
                        <td class="p-3 text-slate-700 dark:text-slate-300">
                            {{ $v->purpose }}
                        </td>
                        <td class="p-3 font-semibold text-purple-600 dark:text-purple-400">
                            {{ $v->person_to_meet ?? 'Principal / Admin' }}
                        </td>
                        <td class="p-3 font-mono text-[11px] text-slate-700 dark:text-slate-300">
                            {{ $v->in_time }}
                        </td>
                        <td class="p-3 text-slate-500 whitespace-nowrap">
                            {{ $v->date->format('M d, Y') }}
                        </td>
                        <td class="p-3 font-mono text-slate-500">
                            {{ $v->phone ?? 'N/A' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-6 text-center text-slate-400">No visitors recorded today.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
            {{ $visitors->links() }}
        </div>
    </div>

</div>

<!-- Modal: Add Visitor -->
@if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
<div id="add-visitor-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs hidden p-4">
    <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full shadow-2xl">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Issue Visitor Pass</h3>
            <button onclick="document.getElementById('add-visitor-modal').classList.add('hidden')" class="text-slate-400 hover:text-white text-sm">✕</button>
        </div>

        <form method="POST" action="{{ route('school.front-office.visitor.store') }}" class="space-y-3 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Visitor Name *</label>
                <input type="text" name="name" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
            </div>

            <div class="grid grid-cols-2 gap-2.5">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Phone Number</label>
                    <input type="text" name="phone" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">ID Proof Reference</label>
                    <input type="text" name="id_proof" placeholder="Aadhaar / Voter ID" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2.5">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Purpose of Visit *</label>
                    <input type="text" name="purpose" placeholder="Admission Inquiry" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Person to Meet</label>
                    <input type="text" name="person_to_meet" placeholder="Principal / Admission Head" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>
            </div>

            <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="document.getElementById('add-visitor-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Cancel</button>
                <button type="submit" class="primary-btn" style="padding: 0.4rem 1.1rem; font-size: 0.75rem;">Generate Pass</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Log Call -->
<div id="add-call-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs hidden p-4">
    <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full shadow-2xl">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Log Telephone Call</h3>
            <button onclick="document.getElementById('add-call-modal').classList.add('hidden')" class="text-slate-400 hover:text-white text-sm">✕</button>
        </div>

        <form method="POST" action="{{ route('school.front-office.call.store') }}" class="space-y-3 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Caller Name *</label>
                <input type="text" name="name" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
            </div>

            <div class="grid grid-cols-2 gap-2.5">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Phone Number *</label>
                    <input type="text" name="phone" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Call Type</label>
                    <select name="call_type" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-bold">
                        <option value="incoming" selected>Incoming Call</option>
                        <option value="outgoing">Outgoing Call</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Call Summary / Notes</label>
                <textarea name="description" rows="2" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"></textarea>
            </div>

            <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="document.getElementById('add-call-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Cancel</button>
                <button type="submit" class="primary-btn" style="padding: 0.4rem 1.1rem; font-size: 0.75rem;">Save Call Log</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Postal Dispatch -->
<div id="add-postal-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs hidden p-4">
    <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full shadow-2xl">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Register Postal Mail</h3>
            <button onclick="document.getElementById('add-postal-modal').classList.add('hidden')" class="text-slate-400 hover:text-white text-sm">✕</button>
        </div>

        <form method="POST" action="{{ route('school.front-office.postal.store') }}" class="space-y-3 text-xs">
            @csrf
            <div class="grid grid-cols-2 gap-2.5">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">From Title *</label>
                    <input type="text" name="from_title" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">To Title *</label>
                    <input type="text" name="to_title" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2.5">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Reference No</label>
                    <input type="text" name="reference_no" placeholder="POST-REG-2026" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Type</label>
                    <select name="type" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-bold">
                        <option value="dispatch" selected>Outbound Dispatch</option>
                        <option value="receive">Inbound Received Mail</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="document.getElementById('add-postal-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Cancel</button>
                <button type="submit" class="primary-btn" style="padding: 0.4rem 1.1rem; font-size: 0.75rem;">Record Mail</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
