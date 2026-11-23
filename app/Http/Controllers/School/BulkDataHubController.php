<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Mail\UserCredentialsMailable;
use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\AcademySetting;
use App\Models\User;
use App\Services\BulkImportService;
use App\Services\SchoolAcademicAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BulkDataHubController extends Controller
{
    public function __construct(
        protected BulkImportService $bulkImportService
    ) {}

    protected function checkAdminAccess(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Unauthorized. Bulk data import and export is restricted to administrators.');
        }
    }

    /**
     * Display Unified Bulk Ingestion & Export Hub.
     */
    public function index(Request $request): View
    {
        $this->checkAdminAccess();

        $activeTab = $request->query('tab', 'students');
        $classes = AcademicClass::orderBy('grade_level')->orderBy('section')->get();

        $stats = [
            'total_students' => User::where('role', 'student')->count(),
            'total_parents' => User::where('role', 'parent')->count(),
            'total_teachers' => User::where('role', 'teacher')->count(),
            'total_admins' => User::where('role', 'admin')->count(),
            'total_classes' => AcademicClass::count(),
            'total_subjects' => AcademicSubject::count(),
        ];

        return view('school.bulk.index', compact('activeTab', 'classes', 'stats'));
    }

    /**
     * Download sample CSV templates.
     */
    public function sampleCsv(string $type): StreamedResponse
    {
        // Sample templates are public header outlines with zero private institutional data
        if (! in_array($type, ['students', 'staff', 'classes_subjects', 'parents'], true)) {
            abort(404, 'Invalid template requested.');
        }

        return $this->bulkImportService->getSampleCsv($type);
    }

    /**
     * Bulk import students from CSV.
     */
    public function importStudents(Request $request): RedirectResponse
    {
        $this->checkAdminAccess();

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $createParents = $request->boolean('create_parent_accounts', true);

        $result = $this->bulkImportService->importStudentsFromCsv(
            $request->file('csv_file'),
            true,
            $createParents
        );

        $isOnboarding = $request->input('source') === 'onboarding' || $request->has('onboarding_step');
        $onboardingStep = (int) $request->input('onboarding_step', 5);

        if (! $result['success']) {
            if ($isOnboarding) {
                return redirect()->route('school.onboarding.index', ['step' => $onboardingStep])
                    ->withErrors($result['errors']);
            }

            return redirect()->route('school.bulk.index', ['tab' => 'students'])
                ->withErrors($result['errors']);
        }

        if (! empty($result['credentials'])) {
            session(['bulk_last_credentials' => $result['credentials']]);
            session()->flash('bulk_last_credentials', $result['credentials']);
        }

        $msg = "Successfully imported {$result['total']} student records ({$result['created']} created, {$result['updated']} updated).";
        if (! empty($result['credentials'])) {
            $msg .= ' Generated '.count($result['credentials']).' new login credentials available for export and dispatch below.';
        }
        if (! empty($result['parents_created'])) {
            $msg .= " Additionally generated and linked {$result['parents_created']} verified Parent/Guardian accounts!";
        }
        if (! empty($result['errors'])) {
            $msg .= ' Warning: '.implode(' ', $result['errors']);
        }

        if ($isOnboarding) {
            AcademySetting::set('onboarding_step_5_done', 'true');

            return redirect()->route('school.onboarding.index', ['step' => $onboardingStep])
                ->with('success', $msg);
        }

        return redirect()->route('school.bulk.index', ['tab' => 'students'])
            ->with('success', $msg);
    }

    /**
     * Bulk export students to CSV.
     */
    public function exportStudents(Request $request): StreamedResponse
    {
        $this->checkAdminAccess();

        $classId = $request->input('class_id');
        $query = User::where('role', 'student')
            ->with(['currentClass'])
            ->orderBy('current_class_id')
            ->orderBy('roll_number');

        if ($classId) {
            $query->where('current_class_id', $classId);
        }

        $students = $query->get();

        return response()->stream(function () use ($students) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Admission No',
                'Roll No',
                'Class',
                'Section',
                'Student Name',
                'Email',
                'Phone',
                'Guardian Name',
                'Guardian Phone',
                'Guardian Email',
                'Blood Group',
            ]);

            foreach ($students as $s) {
                fputcsv($handle, [
                    $s->admission_number ?? $s->student_id_number,
                    $s->roll_number,
                    $s->currentClass ? $s->currentClass->name : 'N/A',
                    $s->currentClass ? $s->currentClass->section : 'N/A',
                    $s->name,
                    $s->email,
                    $s->phone,
                    $s->guardian_name,
                    $s->guardian_phone,
                    $s->guardian_email,
                    $s->blood_group,
                ]);
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="school_students_master_'.date('Y-m-d').'.csv"',
        ]);
    }

    /**
     * Bulk import staff / teachers / admins from CSV.
     */
    public function importStaff(Request $request): RedirectResponse
    {
        $this->checkAdminAccess();

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $autoPassword = $request->boolean('auto_generate_password', true);
        $result = $this->bulkImportService->importStaffFromCsv($request->file('csv_file'), $autoPassword);

        $isOnboarding = $request->input('source') === 'onboarding' || $request->has('onboarding_step');
        $onboardingStep = (int) $request->input('onboarding_step', 4);

        if (! $result['success']) {
            if ($isOnboarding) {
                return redirect()->route('school.onboarding.index', ['step' => $onboardingStep])
                    ->withErrors($result['errors']);
            }

            return redirect()->route('school.bulk.index', ['tab' => 'staff'])
                ->withErrors($result['errors']);
        }

        if (! empty($result['credentials'])) {
            session(['bulk_last_credentials' => $result['credentials']]);
            session()->flash('bulk_last_credentials', $result['credentials']);
        }

        $msg = "Successfully ingested {$result['total']} faculty & staff members ({$result['created']} created, {$result['updated']} updated).";

        if ($isOnboarding) {
            AcademySetting::set('onboarding_step_4_done', 'true');

            return redirect()->route('school.onboarding.index', ['step' => $onboardingStep])
                ->with('success', $msg);
        }

        return redirect()->route('school.bulk.index', ['tab' => 'staff'])
            ->with('success', $msg);
    }

    /**
     * Bulk import parents and link to students from CSV.
     */
    public function importParents(Request $request): RedirectResponse
    {
        $this->checkAdminAccess();

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $autoPassword = $request->boolean('auto_generate_password', true);
        $result = $this->bulkImportService->importParentsFromCsv($request->file('csv_file'), $autoPassword);

        if (! $result['success']) {
            return redirect()->route('school.bulk.index', ['tab' => 'parents'])
                ->withErrors($result['errors']);
        }

        if (! empty($result['credentials'])) {
            session(['bulk_last_credentials' => $result['credentials']]);
            session()->flash('bulk_last_credentials', $result['credentials']);
        }

        $msg = "Successfully ingested {$result['total']} parent/guardian records ({$result['created']} created, {$result['updated']} updated).";
        if (! empty($result['linked'])) {
            $msg .= " Verified & linked to {$result['linked']} student academic files!";
        }
        if (! empty($result['errors'])) {
            $msg .= ' Warning: '.implode(' ', $result['errors']);
        }

        return redirect()->route('school.bulk.index', ['tab' => 'parents'])
            ->with('success', $msg);
    }

    /**
     * Bulk export parents to CSV.
     */
    public function exportParents(): StreamedResponse
    {
        $this->checkAdminAccess();

        $parents = User::where('role', 'parent')
            ->orderBy('name')
            ->get();

        return response()->stream(function () use ($parents) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Parent Name', 'Email', 'Phone', 'Address', 'Linked Students (Admission Nos & Names)']);

            foreach ($parents as $p) {
                $children = User::childrenOf($p)
                    ->get();

                $childrenDesc = $children->map(fn ($c) => ($c->admission_number ?? $c->student_id_number ?? 'N/A').' - '.$c->name)->implode('; ');

                fputcsv($handle, [
                    $p->name,
                    $p->email,
                    $p->phone,
                    $p->address,
                    $childrenDesc ?: 'No linked student',
                ]);
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="school_parents_master_'.date('Y-m-d').'.csv"',
        ]);
    }

    /**
     * Export freshly generated temporary credentials for administrative distribution.
     */
    public function exportCredentials(): StreamedResponse|RedirectResponse
    {
        $this->checkAdminAccess();

        $credentials = session('bulk_last_credentials', []);
        if (empty($credentials)) {
            return redirect()->route('school.bulk.index')
                ->with('error', 'No recent temporary credentials found in session. Credentials expire after page session terminates.');
        }

        return $this->bulkImportService->exportCredentialsCsv($credentials);
    }

    /**
     * Bulk send login credentials via email to newly created users.
     */
    public function sendBulkCredentials(Request $request): RedirectResponse
    {
        $this->checkAdminAccess();

        $credentials = session('bulk_last_credentials', []);
        if (empty($credentials)) {
            return redirect()->route('school.bulk.index')
                ->with('error', 'No pending credentials available for batch dispatch. Import a new batch or reset account individually.');
        }

        $sentCount = 0;
        $failedCount = 0;

        foreach ($credentials as $cred) {
            $user = User::find($cred['user_id'] ?? null);
            if (! $user || empty($cred['password']) || empty($user->email)) {
                $failedCount++;

                continue;
            }

            try {
                Mail::to($user->email)
                    ->send(new UserCredentialsMailable($user, $cred['password']));
                $sentCount++;
            } catch (\Throwable $e) {
                // If mail transport fails (e.g. no SMTP configured), log error and continue
                Log::warning("Failed to dispatch credential email to {$user->email}: ".$e->getMessage());
                $failedCount++;
            }
        }

        $msg = "Batch Credentials Dispatch: Sent {$sentCount} official login credential emails.";
        if ($failedCount > 0) {
            $msg .= " ({$failedCount} could not be dispatched; verify SMTP mailer settings).";
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Bulk export staff to CSV.
     */
    public function exportStaff(): StreamedResponse
    {
        $this->checkAdminAccess();

        $staff = User::whereIn('role', ['teacher', 'admin'])
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        return response()->stream(function () use ($staff) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Name', 'Email', 'Phone', 'Role', 'Employee ID', 'Designation', 'Status']);

            foreach ($staff as $st) {
                fputcsv($handle, [
                    $st->name,
                    $st->email,
                    $st->phone,
                    $st->role,
                    $st->student_id_number,
                    $st->bio,
                    $st->is_approved ? 'Active' : 'Pending',
                ]);
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="school_faculty_staff_'.date('Y-m-d').'.csv"',
        ]);
    }

    /**
     * Bulk import classes and subjects.
     */
    public function importClassesAndSubjects(Request $request): RedirectResponse
    {
        $this->checkAdminAccess();

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $result = $this->bulkImportService->importClassesAndSubjectsFromCsv($request->file('csv_file'), Auth::user(), $request);

        $isOnboarding = $request->input('source') === 'onboarding' || $request->has('onboarding_step');
        $onboardingStep = (int) $request->input('onboarding_step', 3);

        if (! $result['success']) {
            if ($isOnboarding) {
                return redirect()->route('school.onboarding.index', ['step' => $onboardingStep])
                    ->withErrors($result['errors']);
            }

            return redirect()->route('school.bulk.index', ['tab' => 'classes_subjects'])
                ->withErrors($result['errors']);
        }

        app(SchoolAcademicAuditService::class)->log(
            Auth::user(),
            'bulk_imported_classes_subjects',
            "Imported {$result['classes_count']} classes and {$result['subjects_count']} class-subject mappings from CSV.",
            null,
            ['classes_count' => $result['classes_count'], 'subjects_count' => $result['subjects_count']],
            $request
        );
        $msg = "Successfully ingested {$result['classes_count']} academic classes and {$result['subjects_count']} subjects.";

        if ($isOnboarding) {
            AcademySetting::set('onboarding_step_3_done', 'true');

            return redirect()->route('school.onboarding.index', ['step' => $onboardingStep])
                ->with('success', $msg);
        }

        return redirect()->route('school.bulk.index', ['tab' => 'classes_subjects'])
            ->with('success', $msg);
    }

    /**
     * Bulk export classes & subjects.
     */
    public function exportClassesAndSubjects(): StreamedResponse
    {
        $this->checkAdminAccess();

        $subjects = AcademicSubject::with('academicClass')
            ->orderBy('class_id')
            ->get();

        return response()->stream(function () use ($subjects) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Grade Level', 'Class Name', 'Section', 'Room Number', 'Subject Name', 'Subject Code', 'Weekly Periods']);

            foreach ($subjects as $sub) {
                fputcsv($handle, [
                    $sub->academicClass?->grade_level,
                    $sub->academicClass?->name,
                    $sub->academicClass?->section,
                    $sub->academicClass?->room_number,
                    $sub->name,
                    $sub->code,
                    $sub->weekly_periods,
                ]);
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="school_classes_subjects_'.date('Y-m-d').'.csv"',
        ]);
    }
}
