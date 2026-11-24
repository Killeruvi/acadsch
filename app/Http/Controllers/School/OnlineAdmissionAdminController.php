<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AcademicClass;
use App\Models\AcademySetting;
use App\Models\ClassEnrollment;
use App\Models\OnlineAdmission;
use App\Models\StudentAdmissionDocument;
use App\Models\User;
use App\Services\SchoolAcademicAuditService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OnlineAdmissionAdminController extends Controller
{
    /**
     * Display admissions review register.
     */
    public function index(Request $request): View
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        $query = OnlineAdmission::with(['targetClass', 'enrolledUser'])
            ->latest();

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('application_status', $request->input('status'));
        }

        if ($request->filled('class_id')) {
            $query->where('target_class_id', $request->input('class_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('application_number', 'like', "%{$search}%")
                    ->orWhere('student_first_name', 'like', "%{$search}%")
                    ->orWhere('student_last_name', 'like', "%{$search}%")
                    ->orWhere('father_name', 'like', "%{$search}%")
                    ->orWhere('father_phone', 'like', "%{$search}%");
            });
        }

        $applications = $query->paginate(20)->withQueryString();
        $classes = AcademicClass::orderBy('grade_level')->get();

        $stats = [
            'total' => OnlineAdmission::count(),
            'submitted' => OnlineAdmission::where('application_status', 'submitted')->count(),
            'under_review' => OnlineAdmission::where('application_status', 'under_review')->count(),
            'shortlisted' => OnlineAdmission::where('application_status', 'shortlisted')->count(),
            'admitted' => OnlineAdmission::where('application_status', 'admitted')->count(),
            'rejected' => OnlineAdmission::where('application_status', 'rejected')->count(),
        ];

        return view('school.admissions.online_index', compact('applications', 'classes', 'stats'));
    }

    /**
     * Show detailed application dossier and documents.
     */
    public function show(OnlineAdmission $admission): View
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        $admission->load(['targetClass.sections', 'reviewer', 'enrolledUser']);
        $classes = AcademicClass::orderBy('grade_level')->get();

        return view('school.admissions.online_show', compact('admission', 'classes'));
    }

    /**
     * Update application status and admin notes.
     */
    public function updateStatus(Request $request, OnlineAdmission $admission): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        $validated = $request->validate([
            'application_status' => 'required|in:submitted,under_review,shortlisted,admitted,rejected',
            'review_notes' => 'nullable|string|max:2000',
        ]);

        $validated['reviewed_by'] = Auth::id();
        $admission->update($validated);

        return back()->with('success', "Application status updated to '{$admission->application_status}'.");
    }

    /**
     * 1-Click Enrollment Conversion: Convert verified application into active student and parent accounts.
     */
    public function convertToStudent(Request $request, OnlineAdmission $admission): RedirectResponse
    {
        $admin = Auth::user();
        abort_unless($admin && $admin->isAdmin(), 403);

        if ($admission->enrolled_user_id) {
            return back()->withErrors(['admission' => 'This application has already been converted to an enrolled student.']);
        }

        $validated = $request->validate([
            'class_id' => 'required|exists:academic_classes,id',
            'admission_number' => 'nullable|string|max:50|unique:users,admission_number',
            'roll_number' => 'nullable|string|max:30',
            'student_email' => 'nullable|email|max:150|unique:users,email',
            'password' => 'nullable|string|min:6',
        ]);

        $enrolledStudent = DB::transaction(function () use ($admission, $validated, $admin, $request) {
            $year = date('Y');
            $admNumber = ! empty($validated['admission_number'])
                ? trim($validated['admission_number'])
                : sprintf('SCH-%s-%04d', $year, User::where('role', 'student')->count() + 1);

            $email = ! empty($validated['student_email'])
                ? strtolower(trim($validated['student_email']))
                : sprintf('student.%s@school.internal', strtolower(Str::slug($admission->full_name.'-'.$admNumber)));

            $passwordPlain = ! empty($validated['password'])
                ? $validated['password']
                : ($admission->dob ? $admission->dob->format('dmY') : 'Student@123');

            // 1. Find or create Parent Account
            $parent = null;
            if ($admission->father_phone || $admission->father_email) {
                $parent = User::where('role', 'parent')
                    ->where(function ($q) use ($admission) {
                        if ($admission->father_phone) {
                            $q->where('phone', $admission->father_phone);
                        }
                        if ($admission->father_email) {
                            $q->orWhere('email', $admission->father_email);
                        }
                    })
                    ->first();

                if (! $parent) {
                    $parentEmail = $admission->father_email ?: sprintf('parent.%s@school.internal', Str::slug($admission->father_name.'-'.Str::random(4)));
                    $parent = User::create([
                        'name' => $admission->father_name,
                        'email' => $parentEmail,
                        'phone' => $admission->father_phone,
                        'role' => 'parent',
                        'password' => Hash::make('Parent@123'),
                        'is_approved' => true,
                    ]);
                }
            }

            // 2. Create Student Account
            $student = User::create([
                'name' => $admission->full_name,
                'email' => $email,
                'phone' => $admission->father_phone,
                'role' => 'student',
                'password' => Hash::make($passwordPlain),
                'admission_number' => $admNumber,
                'roll_number' => $validated['roll_number'] ?? null,
                'dob' => $admission->dob,
                'gender' => $admission->gender,
                'blood_group' => $admission->blood_group,
                'nationality' => $admission->nationality,
                'religion' => $admission->religion,
                'caste_category' => $admission->caste_category,
                'previous_school' => $admission->previous_school_name,
                'father_name' => $admission->father_name,
                'mother_name' => $admission->mother_name,
                'guardian_name' => $admission->father_name,
                'guardian_phone' => $admission->father_phone,
                'guardian_email' => $admission->father_email,
                'address' => $admission->guardian_address,
                'city' => $admission->city,
                'state' => $admission->state,
                'pincode' => $admission->pincode,
                'parent_id' => $parent?->id,
                'current_class_id' => $validated['class_id'],
                'is_approved' => true,
            ]);

            // 3. Create Class Enrollment
            ClassEnrollment::create([
                'student_id' => $student->id,
                'class_id' => $validated['class_id'],
                'academic_year' => $admission->academic_year,
                'enrollment_date' => now()->toDateString(),
                'status' => 'active',
            ]);

            // 4. Archive uploaded documents into student_admission_documents
            $docMap = [
                'birth_certificate' => $admission->birth_certificate_path,
                'transfer_certificate' => $admission->transfer_certificate_path,
                'previous_marksheet' => $admission->previous_marksheet_path,
                'photo' => $admission->student_photo_path,
            ];

            foreach ($docMap as $type => $path) {
                if ($path) {
                    StudentAdmissionDocument::create([
                        'student_id' => $student->id,
                        'document_type' => $type,
                        'original_filename' => basename($path),
                        'file_path' => $path,
                        'mime_type' => 'application/octet-stream',
                        'file_size' => 1024,
                        'uploaded_by' => $admin->id,
                    ]);
                }
            }

            // 5. Update OnlineAdmission Status
            $admission->update([
                'application_status' => 'admitted',
                'enrolled_user_id' => $student->id,
                'reviewed_by' => $admin->id,
            ]);

            app(SchoolAcademicAuditService::class)->log(
                $admin,
                'online_admission_converted',
                "Converted application {$admission->application_number} to enrolled scholar {$student->name} ({$admNumber}).",
                ['application_id' => $admission->id],
                ['student_id' => $student->id, 'admission_number' => $admNumber, 'class_id' => $validated['class_id']],
                $request,
                $validated['class_id']
            );

            return $student;
        });

        return redirect()->route('school.students.show', $enrolledStudent->id)
            ->with('success', "Application successfully converted to enrolled scholar '{$enrolledStudent->name}' ({$enrolledStudent->admission_number}). Parent credentials linked.");
    }
}
