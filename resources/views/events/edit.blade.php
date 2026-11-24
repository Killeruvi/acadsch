@extends('layouts.app')

@section('title', 'Event: ' . $event->title . ' — ' . ($school['name'] ?? config('app.name', 'School ERP')))

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('events.show', $event->id) }}" class="text-xs font-bold text-slate-500 hover:text-emerald-500 flex items-center space-x-1">
            <span>&larr; Back to Event Page</span>
        </a>
        <span class="text-xs font-bold px-2 py-0.5 rounded uppercase tracking-wider {{ $event->status === 'live' ? 'bg-rose-100 text-rose-800' : ($event->status === 'completed' ? 'bg-slate-100 text-slate-700' : 'bg-emerald-100 text-emerald-800') }}">
            {{ $event->status }}
        </span>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xs">
        <h1 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white mb-1">Edit School Event / Activity</h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-6">Modify date, venue, rules, or status for this debate or talent organization.</p>

        <form action="{{ route('events.update', $event->id) }}" method="POST" class="space-y-4 text-xs">
            @csrf
            @method('PUT')

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Event Title</label>
                <input type="text" name="title" value="{{ old('title', $event->title) }}" required class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Category</label>
                    <select name="category" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                        <option value="debate" {{ $event->category === 'debate' ? 'selected' : '' }}>Debate &amp; Discourse</option>
                        <option value="talent_show" {{ $event->category === 'talent_show' ? 'selected' : '' }}>Talent Show &amp; Cultural</option>
                        <option value="science_exhibition" {{ $event->category === 'science_exhibition' ? 'selected' : '' }}>Science Exhibition</option>
                        <option value="hackathon" {{ $event->category === 'hackathon' ? 'selected' : '' }}>Computational Hackathon</option>
                        <option value="literary" {{ $event->category === 'literary' ? 'selected' : '' }}>Literary &amp; Creative Arts</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Format</label>
                    <select name="event_format" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                        <option value="individual" {{ $event->event_format === 'individual' ? 'selected' : '' }}>Solo Contestant</option>
                        <option value="parliamentary_team" {{ $event->event_format === 'parliamentary_team' ? 'selected' : '' }}>Parliamentary 2-Person Team</option>
                        <option value="group" {{ $event->event_format === 'group' ? 'selected' : '' }}>Ensemble / Squad</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                        <option value="upcoming" {{ $event->status === 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                        <option value="live" {{ $event->status === 'live' ? 'selected' : '' }}>Live Arena In-Progress</option>
                        <option value="completed" {{ $event->status === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ $event->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Event Date &amp; Time</label>
                    <input type="datetime-local" name="event_datetime" value="{{ old('event_datetime', $event->event_datetime ? $event->event_datetime->format('Y-m-d\TH:i') : '') }}" required class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Venue / Hall</label>
                    <input type="text" name="venue" value="{{ old('venue', $event->venue) }}" required class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Description &amp; Theme</label>
                <textarea name="description" rows="3" required class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">{{ old('description', $event->description) }}</textarea>
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Rules, Rubric &amp; Parliamentary Motions</label>
                <textarea name="rules_and_rubric" rows="3" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">{{ old('rules_and_rubric', $event->rules_and_rubric) }}</textarea>
            </div>

            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <a href="{{ route('events.show', $event->id) }}" class="px-4 py-2 text-slate-500 hover:text-slate-700 font-bold">
                    Cancel
                </a>
                <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-black rounded-xl shadow-xs transition">
                    Save Event Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
