<?php

namespace App\Http\Controllers;

use App\Models\AcademyEvent;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AcademyEventController extends Controller
{
    public function index(Request $request): View
    {
        $categoryFilter = $request->query('category', 'all');
        $statusFilter = $request->query('status', 'all');

        $query = AcademyEvent::with('coordinator');

        if ($categoryFilter !== 'all' && ! empty($categoryFilter)) {
            $query->where('category', $categoryFilter);
        }

        if ($statusFilter !== 'all' && ! empty($statusFilter)) {
            $query->where('status', $statusFilter);
        }

        $events = $query->orderBy('event_datetime', 'desc')->paginate(6)->withQueryString();

        return view('events.index', compact('events', 'categoryFilter', 'statusFilter'));
    }

    public function show(AcademyEvent $event): View
    {
        $event->load('coordinator');
        $user = Auth::user();

        $participants = [];
        if (! empty($event->registered_participants)) {
            $participants = User::whereIn('id', $event->registered_participants)->get();
        }

        $isRegistered = in_array($user->id, $event->registered_participants ?? [], true);

        return view('events.show', compact('event', 'participants', 'isRegistered'));
    }

    public function register(Request $request, AcademyEvent $event): RedirectResponse
    {
        $user = Auth::user();
        $registered = $event->registered_participants ?? [];

        if (in_array($user->id, $registered, true)) {
            return back()->with('info', 'You are already registered for this event!');
        }

        $registered[] = $user->id;
        $event->update(['registered_participants' => $registered]);

        $user->recordDailyActivity(50);

        return back()->with('success', "Registration confirmed for {$event->title}! +50 XP awarded. 🔥");
    }

    public function unregister(Request $request, AcademyEvent $event): RedirectResponse
    {
        $user = Auth::user();
        $registered = $event->registered_participants ?? [];

        if (! in_array($user->id, $registered, true)) {
            return back()->with('info', 'You are not registered for this event.');
        }

        $registered = array_values(array_filter($registered, fn ($id) => (int) $id !== (int) $user->id));
        $event->update(['registered_participants' => $registered]);

        return back()->with('success', "You have unregistered from {$event->title}.");
    }

    public function exportParticipantsCsv(AcademyEvent $event): StreamedResponse
    {
        $user = Auth::user();
        if (! $user->isTeacher() && ! $user->isAdmin()) {
            abort(403);
        }

        $participants = [];
        if (! empty($event->registered_participants)) {
            $participants = User::whereIn('id', $event->registered_participants)->get();
        }

        $filename = 'event_'.Str::slug($event->title).'_participants.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($participants, $event) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['Student ID', 'Full Name', 'Email', 'Phone', 'Event', 'Category', 'Date']);

            foreach ($participants as $p) {
                fputcsv($handle, [
                    $p->student_id_number ?: ('PA-'.$p->id),
                    $p->name,
                    $p->email,
                    $p->phone ?: 'N/A',
                    $event->title,
                    $event->category,
                    $event->event_datetime ? $event->event_datetime->format('Y-m-d H:i') : '',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    public function updatePodium(Request $request, AcademyEvent $event): RedirectResponse
    {
        $validated = $request->validate([
            'winner_1st' => 'required|string',
            'winner_2nd' => 'nullable|string',
            'winner_3rd' => 'nullable|string',
            'status' => 'required|in:upcoming,live,completed',
        ]);

        $podium = [
            '1st' => $validated['winner_1st'],
            '2nd' => $validated['winner_2nd'] ?? null,
            '3rd' => $validated['winner_3rd'] ?? null,
        ];

        $event->update([
            'podium_winners' => $podium,
            'status' => $validated['status'],
        ]);

        return back()->with('success', 'Podium winners updated and trophies published!');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|in:debate,talent_show,science_exhibition,hackathon,literary',
            'event_format' => 'required|in:individual,parliamentary_team,group',
            'description' => 'required|string',
            'rules_and_rubric' => 'nullable|string',
            'event_datetime' => 'required|date',
            'venue' => 'required|string|max:255',
        ]);

        $validated['coordinator_id'] = Auth::id();
        $validated['status'] = 'upcoming';

        $event = AcademyEvent::create($validated);

        return redirect()->route('events.show', $event->id)
            ->with('success', "School event '{$event->title}' organized successfully!");
    }

    public function edit(AcademyEvent $event): View
    {
        return view('events.edit', compact('event'));
    }

    public function update(Request $request, AcademyEvent $event): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|in:debate,talent_show,science_exhibition,hackathon,literary',
            'event_format' => 'required|in:individual,parliamentary_team,group',
            'description' => 'required|string',
            'rules_and_rubric' => 'nullable|string',
            'event_datetime' => 'required|date',
            'venue' => 'required|string|max:255',
            'status' => 'required|in:upcoming,live,completed,cancelled',
        ]);

        $event->update($validated);

        return redirect()->route('events.show', $event->id)
            ->with('success', "School event '{$event->title}' updated successfully!");
    }

    public function destroy(AcademyEvent $event): RedirectResponse
    {
        $title = $event->title;
        $event->delete();

        return redirect()->route('events.index')
            ->with('success', "Event '{$title}' was cancelled and deleted.");
    }
}
