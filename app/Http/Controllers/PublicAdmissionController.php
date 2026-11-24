<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\AcademySetting;
use App\Models\OnlineAdmission;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\SchoolBrandingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PublicAdmissionController extends Controller
{
    /**
     * Display public multi-step online admission form.
     */
    public function create(): View
    {
        $classes = AcademicClass::orderBy('grade_level')->get();
        $school = app(SchoolBrandingService::class)->getBranding();
        $academicSession = AcademySetting::get('academic_session', date('Y').'-'.(date('Y') + 1));

        return view('admissions.apply', compact('classes', 'school', 'academicSession'));
    }

    /**
     * Store new online admission application.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Target Class
            'target_class_id' => 'required|exists:academic_classes,id',

            // Scholar Personal Details
            'student_first_name' => 'required|string|max:100',
            'student_last_name' => 'required|string|max:100',
            'dob' => 'required|date|before:today',
            'gender' => 'required|in:male,female,other',
            'blood_group' => 'nullable|string|max:10',
            'nationality' => 'required|string|max:50',
            'religion' => 'nullable|string|max:50',
            'caste_category' => 'required|string|max:50',

            // Previous Academic Background
            'previous_school_name' => 'nullable|string|max:191',
            'previous_class' => 'nullable|string|max:50',
            'previous_marks_percentage' => 'nullable|numeric|min:0|max:100',

            // Parent & Guardian Details
            'father_name' => 'required|string|max:120',
            'father_phone' => 'required|string|max:25',
            'father_email' => 'nullable|email|max:150',
            'father_occupation' => 'nullable|string|max:100',
            'mother_name' => 'required|string|max:120',
            'mother_phone' => 'nullable|string|max:25',
            'mother_email' => 'nullable|email|max:150',
            'mother_occupation' => 'nullable|string|max:100',

            // Residential Address
            'guardian_address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'pincode' => 'required|string|max:20',

            // Uploaded Documents (PDF/JPG/PNG up to 5MB)
            'birth_certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'transfer_certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'previous_marksheet' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'student_photo' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
        ]);

        $validated['academic_year'] = AcademySetting::get('academic_session', date('Y'));
        $validated['application_number'] = OnlineAdmission::generateApplicationNumber();
        $validated['application_status'] = 'submitted';

        // Secure file uploads
        if ($request->hasFile('birth_certificate')) {
            $validated['birth_certificate_path'] = $request->file('birth_certificate')->store('admissions/documents', 'public');
        }
        if ($request->hasFile('transfer_certificate')) {
            $validated['transfer_certificate_path'] = $request->file('transfer_certificate')->store('admissions/documents', 'public');
        }
        if ($request->hasFile('previous_marksheet')) {
            $validated['previous_marksheet_path'] = $request->file('previous_marksheet')->store('admissions/documents', 'public');
        }
        if ($request->hasFile('student_photo')) {
            $validated['student_photo_path'] = $request->file('student_photo')->store('admissions/photos', 'public');
        }

        $admission = OnlineAdmission::create($validated);

        // Notify Admins
        $adminUsers = User::where('role', 'admin')->get();
        foreach ($adminUsers as $admin) {
            NotificationService::notifyUser(
                $admin->id,
                'admission_application',
                "New Admission: {$admission->full_name}",
                "Online application {$admission->application_number} submitted for Grade {$admission->targetClass?->display_name}.",
                route('school.admissions.online.show', $admission->id),
                '📝'
            );
        }

        return redirect()->route('admissions.track', ['app_no' => $admission->application_number])
            ->with('success', "Application submitted successfully! Your tracking application number is {$admission->application_number}.");
    }

    /**
     * Public application tracking and acknowledgement slip.
     */
    public function track(Request $request): View
    {
        $appNo = $request->input('app_no');
        $phone = $request->input('phone');
        $admission = null;

        if ($appNo) {
            $query = OnlineAdmission::where('application_number', trim($appNo));
            if ($phone) {
                $query->where(function ($q) use ($phone) {
                    $q->where('father_phone', trim($phone))
                        ->orWhere('mother_phone', trim($phone));
                });
            }
            $admission = $query->with('targetClass')->first();
        }

        $school = app(SchoolBrandingService::class)->getBranding();

        return view('admissions.status', compact('admission', 'appNo', 'school'));
    }
}
