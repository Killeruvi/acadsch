<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\FrontOfficeCall;
use App\Models\FrontOfficePostalDispatch;
use App\Models\FrontOfficeVisitor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FrontOfficeController extends Controller
{
    public function index(Request $request): View
    {
        $visitors = FrontOfficeVisitor::latest('date')->paginate(10, ['*'], 'visitors_page')->withQueryString();
        $calls = FrontOfficeCall::latest('date')->paginate(10, ['*'], 'calls_page')->withQueryString();
        $postals = FrontOfficePostalDispatch::latest('date')->paginate(10, ['*'], 'postals_page')->withQueryString();

        $stats = [
            'total_visitors_today' => FrontOfficeVisitor::whereDate('date', now()->toDateString())->count(),
            'total_calls_today' => FrontOfficeCall::whereDate('date', now()->toDateString())->count(),
            'total_postals' => FrontOfficePostalDispatch::count(),
        ];

        return view('school.front-office.index', compact('visitors', 'calls', 'postals', 'stats'));
    }

    public function storeVisitor(Request $request): RedirectResponse
    {
        if (! Auth::user()->isAdmin() && ! Auth::user()->isTeacher()) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'nullable|string|max:30',
            'purpose' => 'required|string|max:200',
            'person_to_meet' => 'nullable|string|max:150',
            'in_time' => 'nullable|string|max:20',
            'out_time' => 'nullable|string|max:20',
            'note' => 'nullable|string|max:255',
        ]);

        $validated['date'] = now()->toDateString();
        $validated['in_time'] = ! empty($validated['in_time']) ? $validated['in_time'] : now()->format('h:i A');

        FrontOfficeVisitor::create($validated);

        return redirect()->back()->with('success', "Visitor '{$validated['name']}' logged in visitor register.");
    }

    public function storeCall(Request $request): RedirectResponse
    {
        if (! Auth::user()->isAdmin() && ! Auth::user()->isTeacher()) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'required|string|max:30',
            'description' => 'nullable|string|max:500',
            'next_follow_up_date' => 'nullable|date',
            'call_duration' => 'nullable|string|max:30',
            'call_type' => 'required|string|in:incoming,outgoing',
        ]);

        $validated['date'] = now()->toDateString();

        FrontOfficeCall::create($validated);

        return redirect()->back()->with('success', "Phone call log for '{$validated['name']}' recorded.");
    }

    public function storePostal(Request $request): RedirectResponse
    {
        if (! Auth::user()->isAdmin() && ! Auth::user()->isTeacher()) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'from_title' => 'required|string|max:150',
            'reference_no' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'to_title' => 'required|string|max:150',
            'type' => 'required|string|in:dispatch,receive',
            'note' => 'nullable|string|max:255',
        ]);

        $validated['date'] = now()->toDateString();

        FrontOfficePostalDispatch::create($validated);

        return redirect()->back()->with('success', "Postal {$validated['type']} entry registered successfully.");
    }
}
