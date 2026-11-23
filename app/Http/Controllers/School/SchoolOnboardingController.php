<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\AcademySetting;
use App\Models\SchoolSection;
use App\Models\SchoolStream;
use App\Models\SubjectMaster;
use App\Models\User;
use App\Services\DatabaseSetupService;
use App\Services\SchoolAcademicAuditService;
use App\Services\SchoolBrandingService;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SchoolOnboardingController extends Controller
{
    public function __construct(
        protected DatabaseSetupService $databaseSetupService
    ) {}

    protected function checkAdminAccess(bool $allowInitialInstaller = false): void
    {
        $adminCount = User::where('role', 'admin')->count();
        $user = Auth::user();

        // Only the read-only installer landing page is public before the first admin exists.
        if ($adminCount === 0 && $allowInitialInstaller && ! $user) {
            return;
        }

        if (! $user) {
            redirect()->route('login')
                ->with('info', 'Please sign in with your Administrator credentials to continue the installation wizard.')
                ->throwResponse();
        }

        if (! $user->isAdmin()) {
            abort(403, 'Unauthorized. School Onboarding Wizard is restricted to institutional administrators.');
        }
    }

    /**
     * Display Step-by-Step Institutional Onboarding Wizard.
     */
    public function index(Request $request): View
    {
        $this->checkAdminAccess(true);

        $settings = AcademySetting::pluck('value', 'key')->toArray();
        $dbStatus = $this->databaseSetupService->getStatus();
        $adminCount = User::where('role', 'admin')->count();

        $stats = [
            'total_classes' => AcademicClass::count(),
            'total_subjects' => AcademicSubject::count(),
            'total_teachers' => User::where('role', 'teacher')->count(),
            'total_students' => User::where('role', 'student')->count(),
        ];

        // Step completion indicators
        $checklist = [
            'step1_database' => $dbStatus['connected'] && $adminCount > 0,
            'step2_profile' => ! empty($settings['institute_name']) && ! empty($settings['academic_session']),
            'step3_classes' => $stats['total_classes'] > 0 && $stats['total_subjects'] > 0,
            'step4_faculty' => $stats['total_teachers'] > 0 || ($settings['onboarding_step_4_done'] ?? false) === 'true',
            'step5_students' => $stats['total_students'] > 0 || ($settings['onboarding_step_5_done'] ?? false) === 'true',
        ];

        $completedSteps = count(array_filter($checklist));
        $progressPercent = (int) round(($completedSteps / 5) * 100);

        // Resume step calculation
        if ($request->has('step')) {
            $currentStep = (int) $request->query('step');
        } else {
            $savedStep = (int) ($settings['onboarding_current_step'] ?? 0);
            if ($savedStep >= 1 && $savedStep <= 6) {
                $currentStep = $savedStep;
            } else {
                // Determine first incomplete step
                if (! $checklist['step1_database']) {
                    $currentStep = 1;
                } elseif (! $checklist['step2_profile']) {
                    $currentStep = 2;
                } elseif (! $checklist['step3_classes']) {
                    $currentStep = 3;
                } elseif (! $checklist['step4_faculty']) {
                    $currentStep = 4;
                } elseif (! $checklist['step5_students']) {
                    $currentStep = 5;
                } else {
                    $currentStep = 6;
                }
            }
        }

        if ($currentStep < 1 || $currentStep > 6) {
            $currentStep = 1;
        }

        $classes = AcademicClass::withCount(['subjects', 'students'])->orderBy('grade_level')->get();

        return view('school.onboarding.index', compact(
            'currentStep',
            'settings',
            'dbStatus',
            'adminCount',
            'stats',
            'checklist',
            'progressPercent',
            'classes'
        ));
    }

    /**
     * Create Master Administrator on fresh installation.
     */
    public function createAdmin(Request $request): RedirectResponse
    {
        // First-admin bootstrap is a privileged operation. Require an out-of-band secret
        // configured by the deployer; otherwise any internet visitor could claim the school.
        $installerKey = (string) config('school.installer_key', env('SCHOOL_INSTALLER_KEY', ''));
        $submittedKey = (string) $request->input('installer_key', '');
        abort_if(strlen($installerKey) < 32, 503, 'Initial administrator setup is disabled until SCHOOL_INSTALLER_KEY is configured with at least 32 random characters.');
        abort_unless($submittedKey !== '' && hash_equals($installerKey, $submittedKey), 403, 'Invalid initial setup key.');

        // Never convert an existing student, parent, or staff account into an administrator.
        if (User::where('role', 'admin')->exists()) {
            abort_unless(Auth::check() && Auth::user()->isAdmin(), 403, 'A Master Administrator account already exists.');
            abort(409, 'The initial administrator has already been created. Use account management to manage administrators.');
        }

        $validated = $request->validate([
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255|unique:users,email',
            'admin_password' => [
                'required', 'string', 'min:12', 'confirmed',
                'regex:/[a-z]/', 'regex:/[A-Z]/', 'regex:/[0-9]/',
            ],
        ], [
            'admin_password.min' => 'Use a password with at least 12 characters.',
            'admin_password.confirmed' => 'The password confirmation does not match.',
        ]);

        // Serialize first-admin creation so two simultaneous installer requests cannot
        // create competing privileged accounts. The lock is released automatically.
        $lock = Cache::lock('school-initial-admin-creation', 30);
        try {
            $admin = $lock->block(5, function () use ($validated) {
                return DB::transaction(function () use ($validated) {
                    if (User::where('role', 'admin')->lockForUpdate()->exists()) {
                        abort(409, 'The initial administrator has already been created.');
                    }
                    if (User::where('email', $validated['admin_email'])->exists()) {
                        abort(409, 'This email already belongs to an account. Use a different administrator email.');
                    }

                    return User::create([
                        'name' => $validated['admin_name'],
                        'email' => $validated['admin_email'],
                        'password' => Hash::make($validated['admin_password']),
                        'role' => 'admin',
                        'is_approved' => true,
                    ]);
                });
            });
        } catch (LockTimeoutException $e) {
            return back()->withErrors(['admin_email' => 'Administrator setup is already in progress. Please retry shortly.']);
        }

        Auth::login($admin);
        $request->session()->regenerate();

        AcademySetting::set('onboarding_step_1_done', 'true');
        AcademySetting::set('onboarding_current_step', '2');

        return redirect()->route('school.onboarding.index', ['step' => 2])
            ->with('success', 'Master Administrator account initialized successfully! Proceed with Step 2 (School Profile).');
    }

    /**
     * Reset / Wipe database to a clean slate.
     */
    public function resetDatabase(Request $request): RedirectResponse
    {
        $this->checkAdminAccess();

        $removeAdmin = $request->boolean('remove_admin');
        $user = Auth::user();
        $adminEmail = $user ? $user->email : 'principal@primearc.edu.in';

        $result = $this->databaseSetupService->wipeAndInitializeCleanSlate($adminEmail, 'password123', ! $removeAdmin);

        if (! $result['success']) {
            return redirect()->route('school.onboarding.index', ['step' => 1])
                ->withErrors([$result['message']]);
        }

        if ($removeAdmin) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('school.onboarding.index', ['step' => 1])
                ->with('success', 'Database and Administrator completely wiped! You may now create a brand new Master Administrator from scratch.');
        }

        $admin = User::where('email', $adminEmail)->first();
        if ($admin) {
            Auth::login($admin);
        }

        AcademySetting::set('onboarding_step_1_done', 'true');
        AcademySetting::set('onboarding_current_step', '2');

        return redirect()->route('school.onboarding.index', ['step' => 2])
            ->with('success', 'Database successfully wiped clean! Retained primary administrator. Proceed with Step 2 (School Profile).');
    }

    /**
     * Save Step 2: Institutional Profile.
     */
    public function saveProfile(Request $request): RedirectResponse
    {
        $this->checkAdminAccess();

        $validated = $request->validate([
            'institute_name' => 'required|string|max:255',
            'school_short_name' => 'nullable|string|max:20',
            'campus_name' => 'nullable|string|max:255',
            'motto' => 'nullable|string|max:255',
            'school_logo_url' => 'nullable|string|max:1000',
            'board_name' => 'nullable|string|max:50',
            'affiliation_number' => 'nullable|string|max:255',
            'academic_session' => 'required|string|max:50',
            'principal_name' => 'nullable|string|max:255',
            'institution_phone' => 'nullable|string|max:50',
            'institution_email' => 'nullable|email|max:255',
            'institution_address' => 'nullable|string|max:500',
            'currency_symbol' => 'nullable|string|max:10',
        ]);

        foreach ($validated as $key => $value) {
            AcademySetting::set($key, $value);
        }

        SchoolBrandingService::flush();

        AcademySetting::set('onboarding_step_2_done', 'true');
        AcademySetting::set('onboarding_current_step', '3');

        return redirect()->route('school.onboarding.index', ['step' => 3])
            ->with('success', 'Institutional profile saved! Proceed to Step 3 to configure academic curriculum.');
    }

    /**
     * 1-Click Initialize Standard K-12 Class & Subject Presets.
     */
    public function applyK12Presets(Request $request): RedirectResponse
    {
        $this->checkAdminAccess();

        $board = $request->input('board', 'cbse');
        $academicYear = AcademySetting::get('academic_session');
        if (! $academicYear) {
            return back()->withErrors(['academic_session' => 'Configure the active academic session before applying class presets.']);
        }

        $streamName = config('school.onboarding.default_stream');
        $sectionName = strtoupper((string) config('school.onboarding.default_section'));
        $stream = SchoolStream::active()->where('name', $streamName)->first();
        if (! $stream) {
            return back()->withErrors(['stream' => 'The configured onboarding stream is not available. Create or reactivate it in Stream Master.']);
        }
        $sectionRecord = SchoolSection::where('stream', $stream->name)
            ->where('name', $sectionName)->where('is_active', true)->first();
        if (! $sectionRecord) {
            return back()->withErrors(['section' => 'The configured onboarding section is not available. Create or reactivate it in Section Master.']);
        }
        if (! $sectionRecord->capacity || (int) $sectionRecord->capacity < (int) config('school.capacity.minimum')) {
            return back()->withErrors(['section' => 'Configure a valid capacity for the onboarding section in Section Master.']);
        }

        $createdClasses = 0;
        $createdSubjects = 0;
        $errors = [];

        try {
            DB::transaction(function () use ($academicYear, $stream, $sectionRecord, &$createdClasses, &$createdSubjects, &$errors) {
                foreach (config('school.grade_levels', []) as $grade) {
                    $class = AcademicClass::where('grade_level', $grade)
                        ->where('stream', $stream->name)
                        ->where('section', $sectionRecord->name)
                        ->where('academic_year', $academicYear)
                        ->lockForUpdate()->first();
                    if ($class) {
                        if ($class->section_id && (int) $class->section_id !== (int) $sectionRecord->id) {
                            $errors[] = "Grade {$grade}: Existing class section identity conflicts with Section Master.";

                            continue;
                        }
                        if (! $class->section_id) {
                            $class->update(['section_id' => $sectionRecord->id]);
                        }
                    } else {
                        $class = AcademicClass::create([
                            'name' => 'Class '.$grade,
                            'grade_level' => $grade,
                            'stream' => $stream->name,
                            'section' => $sectionRecord->name,
                            'section_id' => $sectionRecord->id,
                            'academic_year' => $academicYear,
                            'room_number' => $sectionRecord->room_number,
                            'capacity' => $sectionRecord->capacity,
                        ]);
                        $createdClasses++;
                    }

                    $masters = SubjectMaster::getAvailableForClass($class, true);
                    if ($masters->isEmpty()) {
                        $errors[] = "Grade {$grade}: No active subject masters are configured for {$stream->name} and this grade range.";

                        continue;
                    }

                    foreach ($masters as $master) {
                        if (! $master->default_periods || $master->default_periods < 1) {
                            $errors[] = "Grade {$grade}: {$master->name} has no valid default weekly period load in Subject Master.";

                            continue;
                        }
                        $code = $master->generateCodeForGrade($grade);
                        $subject = AcademicSubject::where('class_id', $class->id)->where(function ($query) use ($code, $master) {
                            $query->where('code', $code)->orWhere('subject_master_id', $master->id)->orWhereRaw('UPPER(name) = ?', [mb_strtoupper($master->name)]);
                        })->first();
                        if ($subject) {
                            if (strtoupper($subject->name) !== strtoupper($master->name) || ($subject->subject_master_id && (int) $subject->subject_master_id !== (int) $master->id)) {
                                $errors[] = "Grade {$grade}: Existing subject {$subject->name} is linked to a different subject master.";

                                continue;
                            }
                            if (! $subject->subject_master_id) {
                                $subject->update(['subject_master_id' => $master->id, 'teacher_id' => $subject->teacher_id ?? $master->default_teacher_id]);
                            }
                        } else {
                            AcademicSubject::create([
                                'class_id' => $class->id,
                                'subject_master_id' => $master->id,
                                'teacher_id' => $master->default_teacher_id,
                                'code' => $code,
                                'name' => $master->name,
                                'weekly_periods' => $master->default_periods,
                            ]);
                            $createdSubjects++;
                        }
                    }
                }
                if ($errors) {
                    throw ValidationException::withMessages(['curriculum' => $errors]);
                }
            }, 3);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        if (Auth::user()) {
            app(SchoolAcademicAuditService::class)->log(Auth::user(), 'applied_k12_class_preset', 'Applied K-12 classes and curriculum from configured stream, section and subject masters.', null, ['classes_created' => $createdClasses, 'subjects_created' => $createdSubjects, 'academic_year' => $academicYear, 'stream' => $stream->name, 'section' => $sectionRecord->name], $request);
        }
        AcademySetting::set('onboarding_step_3_done', 'true');
        AcademySetting::set('onboarding_current_step', '4');

        return redirect()->route('school.onboarding.index', ['step' => 4])
            ->with('success', "{$board} K-12 class setup applied from the active stream, section and subject masters. Created {$createdClasses} classes and {$createdSubjects} class-subject mappings.");
    }

    /**
     * Skip step and advance resume tracker.
     */
    public function skipStep(int $step): RedirectResponse
    {
        $this->checkAdminAccess();

        $nextStep = min(6, $step + 1);
        AcademySetting::set('onboarding_step_'.$step.'_done', 'true');
        AcademySetting::set('onboarding_current_step', (string) $nextStep);

        return redirect()->route('school.onboarding.index', ['step' => $nextStep])
            ->with('info', "Step {$step} marked for later review. Resuming at Step {$nextStep}.");
    }

    /**
     * Complete onboarding wizard.
     */
    public function finish(): RedirectResponse
    {
        $this->checkAdminAccess();

        AcademySetting::set('onboarding_completed', 'true');
        AcademySetting::set('onboarding_completed_at', now()->toDateTimeString());
        AcademySetting::set('onboarding_current_step', '6');

        return redirect()->route('school.classes.index')
            ->with('success', '🎉 Congratulations! Your School Management System is fully configured and ready for live campus operations.');
    }
}
