<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AdmissionEnquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdmissionEnquiryController extends Controller
{
    public function storePublic(Request $request): RedirectResponse
    {
        // Honeypot field is intentionally invisible to real visitors.
        if (filled($request->input('website'))) {
            return back()->with('status', 'Thank you. Your enquiry has been received.');
        }

        $data = $request->validate([
            'student_name' => ['required', 'string', 'max:120'],
            'applying_for_class' => ['required', 'string', 'max:40'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'guardian_name' => ['required', 'string', 'max:120'],
            'guardian_phone' => ['required', 'string', 'max:25'],
            'guardian_email' => ['nullable', 'email', 'max:150'],
            'preferred_contact_time' => ['nullable', 'string', 'max:80'],
            'previous_school' => ['nullable', 'string', 'max:150'],
            'message' => ['nullable', 'string', 'max:1500'],
        ]);

        $data['guardian_phone'] = trim($data['guardian_phone']);
        $data['guardian_email'] = isset($data['guardian_email']) ? mb_strtolower(trim($data['guardian_email'])) : null;
        $data['status'] = 'new';
        AdmissionEnquiry::create($data);

        return back()->with('status', 'Thank you. The school has received the admission enquiry and will contact the guardian.');
    }

    public function index(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['new', 'contacted', 'visit_scheduled', 'approved', 'rejected', 'converted'])],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $query = AdmissionEnquiry::query()->latest();
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['search'])) {
            $term = trim($filters['search']);
            $query->where(fn ($q) => $q->where('student_name', 'like', "%{$term}%")
                ->orWhere('guardian_name', 'like', "%{$term}%")
                ->orWhere('guardian_phone', 'like', "%{$term}%")
                ->orWhere('guardian_email', 'like', "%{$term}%"));
        }

        $enquiries = $query->paginate(20)->withQueryString();
        $counts = AdmissionEnquiry::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('school.admissions.enquiries', compact('enquiries', 'counts', 'filters'));
    }

    public function update(Request $request, AdmissionEnquiry $enquiry): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $data = $request->validate([
            'status' => ['required', Rule::in(['new', 'contacted', 'visit_scheduled', 'approved', 'rejected', 'converted'])],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $data['contacted_at'] = in_array($data['status'], ['contacted', 'visit_scheduled', 'approved', 'converted'], true)
            ? ($enquiry->contacted_at ?? now()) : $enquiry->contacted_at;
        $enquiry->update($data);

        return back()->with('success', 'Admission enquiry updated.');
    }
}
