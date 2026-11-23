<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AcademicClass;
use App\Models\AcademySetting;
use App\Models\Batch;
use App\Models\ClassEnrollment;
use App\Models\FeeInvoice;
use App\Models\StudentAdmissionDocument;
use App\Models\StudentTransportAssignment;
use App\Models\TransportAuditLog;
use App\Models\TransportRoute;
use App\Models\TransportRouteStop;
use App\Models\User;
use App\Services\SchoolBrandingService;
use App\Services\StudentIdCardVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SchoolStudentController extends Controller
{
    /**
     * Get class IDs assigned to teacher (homeroom, subject, or timetable).
     */
    protected function getTeacherAssignedClassIds(int $teacherId): array
    {
        return AcademicClass::query()
            ->where(function ($query) use ($teacherId) {
                $query->where('class_teacher_id', $teacherId)
                    ->orWhereHas('subjects', fn ($q) => $q->where('teacher_id', $teacherId))
                    ->orWhereHas('timetables', fn ($q) => $q->where('teacher_id', $teacherId));
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** Enforce class-level isolation on every student record endpoint. */
    protected function authorizeStudentScope(User $student, User $actor): void
    {
        abort_unless($actor->isAdmin() || $actor->isTeacher(), 403);

        if ($actor->isTeacher()) {
            $assignedClassIds = $this->getTeacherAssignedClassIds($actor->id);
            $currentClassIsAssigned = $student->current_class_id !== null
                && in_array((int) $student->current_class_id, $assignedClassIds, true);
            $hasAssignedActiveEnrollment = ClassEnrollment::query()
                ->where('student_id', $student->id)
                ->where('status', 'active')
                ->whereIn('class_id', $assignedClassIds)
                ->exists();

            abort_unless($currentClassIsAssigned || $hasAssignedActiveEnrollment, 403,
                'You may only access students in your assigned classes.');
        }
    }

    /**
     * Display student directory with class filters and search.
     */
    public function index(Request $request): View
    {
        if ($request->routeIs('students.index') || ($request->is('students') && ! $request->is('school/students*'))) {
            return $this->lmsIndex($request);
        }

        $user = Auth::user();
        if ($user && ! $user->isAdmin() && ! $user->isTeacher()) {
            abort(403, 'Unauthorized. Student directory access is reserved for faculty and administrators.');
        }
        $filters = $request->validate([
            'class_id' => ['nullable', 'integer', 'exists:academic_classes,id'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $classId = $filters['class_id'] ?? null;
        $search = trim($filters['search'] ?? '');

        $query = User::where('role', 'student')
            ->with(['currentClass', 'parent'])
            ->addSelect(['enrollment_status' => ClassEnrollment::select('status')
                ->whereColumn('class_enrollments.student_id', 'users.id')
                ->whereColumn('class_enrollments.class_id', 'users.current_class_id')
                ->orderByDesc('updated_at')
                ->limit(1)])
            ->orderBy('current_class_id')
            ->orderBy('roll_number');

        $teacherAssignedClassIds = null;
        if ($user && $user->isTeacher()) {
            $teacherAssignedClassIds = $this->getTeacherAssignedClassIds($user->id);
            $query->whereIn('current_class_id', $teacherAssignedClassIds);
        }

        if ($classId) {
            $query->where('current_class_id', $classId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('admission_number', 'like', "%{$search}%")
                    ->orWhere('roll_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('guardian_name', 'like', "%{$search}%")
                    ->orWhere('guardian_email', 'like', "%{$search}%")
                    ->orWhere('guardian_phone', 'like', "%{$search}%")
                    ->orWhere('previous_school_name', 'like', "%{$search}%")
                    ->orWhere('previous_school_tc_number', 'like', "%{$search}%")
                    ->orWhere('student_id_number', 'like', "%{$search}%");
            });
        }

        $students = $query->paginate(20);

        $classesQuery = AcademicClass::withCount(['students' => fn ($q) => $q->wherePivot('status', 'active')])->orderBy('grade_level')->orderBy('section');
        if ($teacherAssignedClassIds !== null) {
            $classesQuery->whereIn('id', $teacherAssignedClassIds);
        }
        $classes = $classesQuery->get();

        $stats = [
            'total_students' => $teacherAssignedClassIds !== null ? User::where('role', 'student')->whereIn('current_class_id', $teacherAssignedClassIds)->count() : User::where('role', 'student')->whereNotNull('current_class_id')->count(),
            'total_classes' => $classes->count(),
            'active_admissions' => $teacherAssignedClassIds !== null ? ClassEnrollment::where('status', 'active')->whereIn('class_id', $teacherAssignedClassIds)->count() : ClassEnrollment::where('status', 'active')->count(),
            'unassigned' => $teacherAssignedClassIds !== null ? 0 : User::where('role', 'student')->whereNull('current_class_id')->count(),
        ];

        if ($request->ajax()) {
            return response()->json([
                'html' => view('school.students.partials.results', compact('students'))->render(),
                'total' => $students->total(),
            ]);
        }

        return view('school.students.index', compact('students', 'classes', 'stats', 'classId', 'search'));
    }

    /**
     * Show formal student admission form.
     */
    public function create(): View
    {
        $academicSession = AcademySetting::get('academic_session');
        $normalizedSession = $this->normalizeAcademicSession($academicSession);
        $classes = AcademicClass::with('feeStructures')
            ->withCount(['students' => fn ($query) => $query
                ->where('class_enrollments.academic_year', $normalizedSession)
                ->where('class_enrollments.status', 'active')])
            ->orderBy('grade_level')
            ->orderBy('section')
            ->get()
            ->filter(function (AcademicClass $class) use ($normalizedSession) {
                $matchingFees = $class->feeStructures->filter(fn ($fee) => $normalizedSession !== null
                    && $this->normalizeAcademicSession($fee->academic_year) === $normalizedSession
                    && (float) $fee->amount > 0
                )->values();
                $class->setRelation('feeStructures', $matchingFees);

                return $normalizedSession !== null
                    && $this->normalizeAcademicSession($class->academic_year) === $normalizedSession
                    && $matchingFees->isNotEmpty();
            })
            ->values();
        $transportRoutes = TransportRoute::with(['stops', 'vehicles'])
            ->where('status', 'active')
            ->orderBy('route_title')
            ->get();
        $suggestedAdmissionNo = User::generateNextAdmissionNumber();

        return view('school.students.create', compact(
            'classes', 'transportRoutes', 'suggestedAdmissionNo', 'academicSession', 'normalizedSession'
        ));
    }

    /**
     * Normalize the two existing session formats (YYYY-YY and YYYY-YYYY)
     * without inventing a current session when the institution has not configured one.
     */
    private function normalizeAcademicSession(?string $session): ?string
    {
        $session = trim((string) $session);
        if (preg_match('/^(\\d{4})-(\\d{2})$/', $session, $matches)) {
            return $matches[1].'-'.((int) $matches[1] + 1);
        }
        if (preg_match('/^(\\d{4})-(\\d{4})$/', $session)) {
            return $session;
        }

        return null;
    }

    /**
     * Store a complete admission atomically. Account credentials are random and
     * activation is performed through Laravel's one-time password-reset flow.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'admission_number' => ['nullable', 'string', 'max:50'],
            'class_id' => ['required', 'integer', 'exists:academic_classes,id'],
            'roll_number' => ['nullable', 'integer', 'min:1'],
            'phone' => ['nullable', 'string', 'max:25'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'blood_group' => ['nullable', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],
            'address' => ['nullable', 'string', 'max:255'],
            'guardian_name' => ['required', 'string', 'max:120'],
            'guardian_phone' => ['required', 'string', 'max:25'],
            'guardian_email' => ['required', 'email', 'max:150'],
            'previous_school_name' => ['nullable', 'string', 'max:150'],
            'previous_school_board' => ['nullable', 'string', 'max:60'],
            'previous_school_year' => ['nullable', 'string', 'max:30'],
            'previous_school_tc_number' => ['nullable', 'string', 'max:60'],
            'previous_school_remarks' => ['nullable', 'string', 'max:500'],
            'transport_opt_in' => ['nullable', 'boolean'],
            'transport_route_id' => ['required_if:transport_opt_in,1', 'nullable', 'integer', 'exists:transport_routes,id'],
            'transport_stop_id' => ['nullable', 'integer', 'exists:transport_route_stops,id'],
            'transport_vehicle_id' => ['nullable', 'integer', 'exists:transport_vehicles,id'],
            'transport_waiver_percentage' => ['nullable', 'numeric', 'between:0,100'],
            'transport_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'documents' => ['nullable', 'array', 'max:3'],
            'documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'document_types' => ['nullable', 'array'],
            'document_types.*' => ['required', Rule::in(['birth_certificate', 'student_photo', 'transfer_certificate', 'previous_marksheet', 'address_proof', 'other'])],
        ]);

        $session = $this->normalizeAcademicSession(AcademySetting::get('academic_session'));
        if (! $session) {
            throw ValidationException::withMessages([
                'class_id' => 'Configure a valid active academic session in School Settings before admitting students.',
            ]);
        }

        $guardianEmail = mb_strtolower(trim($validated['guardian_email']));
        $existingGuardian = User::where('email', $guardianEmail)->first();
        if ($existingGuardian && ! $existingGuardian->isParent()) {
            throw ValidationException::withMessages([
                'guardian_email' => 'This email already belongs to a non-parent account. Use a different guardian email or resolve the account with an administrator.',
            ]);
        }

        $createdParent = false;
        $student = null;
        $class = null;

        DB::transaction(function () use ($validated, $session, $guardianEmail, &$createdParent, &$student, &$class) {
            $sessionSetting = AcademySetting::where('key', 'academic_session')->lockForUpdate()->first();
            $lockedSession = $this->normalizeAcademicSession($sessionSetting?->value);
            if (! $lockedSession || $lockedSession !== $session) {
                throw ValidationException::withMessages([
                    'class_id' => 'The active academic session changed during admission. Reload the form and try again.',
                ]);
            }

            // Lock the class row so concurrent admissions through this endpoint
            // cannot allocate the same roll number or exceed the configured capacity.
            $class = AcademicClass::whereKey($validated['class_id'])->lockForUpdate()->firstOrFail();
            $classSession = $this->normalizeAcademicSession($class->academic_year);
            if (! $classSession || $classSession !== $session) {
                throw ValidationException::withMessages([
                    'class_id' => 'The selected class is not configured for the active academic session ('.$session.').',
                ]);
            }

            $activeCount = ClassEnrollment::where('class_id', $class->id)
                ->where('academic_year', $session)
                ->where('status', 'active')
                ->count();
            if ($class->capacity && $activeCount >= $class->capacity) {
                throw ValidationException::withMessages([
                    'class_id' => 'The selected class has reached its configured capacity.',
                ]);
            }

            $feeStructures = $class->feeStructures()
                ->orderBy('id')
                ->get()
                ->filter(fn ($fee) => $this->normalizeAcademicSession($fee->academic_year) === $session
                    && (float) $fee->amount > 0)
                ->values();
            if ($feeStructures->isEmpty()) {
                throw ValidationException::withMessages([
                    'class_id' => 'No payable fee structure is configured for this class and academic session. Configure the fee structure before admission; no estimated fee will be charged.',
                ]);
            }

            $admissionNo = trim((string) ($validated['admission_number'] ?? ''));
            $admissionTaken = $admissionNo !== '' && (
                User::where('admission_number', $admissionNo)->orWhere('student_id_number', $admissionNo)->exists()
                || ClassEnrollment::where('admission_number', $admissionNo)->exists()
            );
            if ($admissionNo === '' || $admissionTaken) {
                $admissionNo = User::generateNextAdmissionNumber();
            }

            $requestedRoll = isset($validated['roll_number']) ? (int) $validated['roll_number'] : null;
            if ($requestedRoll && $class->capacity && $requestedRoll > $class->capacity) {
                throw ValidationException::withMessages([
                    'roll_number' => 'The requested roll number exceeds the configured class capacity.',
                ]);
            }
            $rollNumber = $requestedRoll && ClassEnrollment::isRollNumberAvailable($class->id, $requestedRoll, null, $session)
                ? $requestedRoll
                : ClassEnrollment::generateNextRollNumber($class->id, null, $session);

            $parent = User::where('email', $guardianEmail)->lockForUpdate()->first();
            if (! $parent) {
                $parent = User::create([
                    'name' => $validated['guardian_name'],
                    'email' => $guardianEmail,
                    'password' => Hash::make(Str::random(64)),
                    'role' => 'parent',
                    'phone' => $validated['guardian_phone'],
                    'address' => $validated['address'] ?? null,
                    'is_approved' => true,
                ]);
                $createdParent = true;
            } elseif (! $parent->isParent()) {
                throw ValidationException::withMessages([
                    'guardian_email' => 'This email is already registered to another account type.',
                ]);
            }

            $student = User::create([
                'name' => $validated['name'],
                'email' => mb_strtolower(trim($validated['email'])),
                'password' => Hash::make(Str::random(64)),
                'role' => 'student',
                'admission_number' => $admissionNo,
                'student_id_number' => $admissionNo,
                'current_class_id' => $class->id,
                'roll_number' => $rollNumber,
                'parent_id' => $parent->id,
                'phone' => $validated['phone'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'blood_group' => $validated['blood_group'] ?? null,
                'address' => $validated['address'] ?? null,
                'guardian_name' => $validated['guardian_name'],
                'guardian_phone' => $validated['guardian_phone'],
                'guardian_email' => $guardianEmail,
                'previous_school_name' => $validated['previous_school_name'] ?? null,
                'previous_school_board' => $validated['previous_school_board'] ?? null,
                'previous_school_year' => $validated['previous_school_year'] ?? null,
                'previous_school_tc_number' => $validated['previous_school_tc_number'] ?? null,
                'previous_school_remarks' => $validated['previous_school_remarks'] ?? null,
                'is_approved' => true,
                'academic_tier' => 'Primary Scholar',
            ]);

            ClassEnrollment::create([
                'class_id' => $class->id,
                'student_id' => $student->id,
                'roll_number' => $rollNumber,
                'admission_number' => $admissionNo,
                'academic_year' => $session,
                'status' => 'active',
            ]);

            // Generate invoices only from configured fee master records. Each
            // frequency remains a separate invoice; no fabricated default amount.
            foreach ($feeStructures as $feeStructure) {
                $amount = round((float) $feeStructure->amount, 2);
                $frequency = in_array($feeStructure->due_frequency, ['monthly', 'quarterly', 'annual'], true)
                    ? $feeStructure->due_frequency
                    : 'manual';

                FeeInvoice::create([
                    'invoice_number' => 'SCH-'.now()->format('Ymd').'-'.Str::upper(Str::random(10)),
                    'student_id' => $student->id,
                    'title' => $feeStructure->fee_title,
                    'category' => $feeStructure->fee_type,
                    'amount' => $amount,
                    'discount' => 0,
                    'tax' => 0,
                    'total_due' => $amount,
                    'paid_amount' => 0,
                    'status' => 'unpaid',
                    'billing_type' => $frequency,
                    'billing_month' => $frequency === 'monthly' ? now()->format('Y-m') : null,
                    'due_date' => now()->addDays(15)->toDateString(),
                    'notes' => 'Generated from fee structure #'.$feeStructure->id.' for academic session '.$session,
                ]);
            }

            if (! empty($validated['transport_opt_in'])) {
                $route = TransportRoute::whereKey($validated['transport_route_id'])
                    ->where('status', 'active')->lockForUpdate()->firstOrFail();
                $stop = null;
                if (! empty($validated['transport_stop_id'])) {
                    $stop = TransportRouteStop::whereKey($validated['transport_stop_id'])
                        ->where('route_id', $route->id)->firstOrFail();
                }
                $vehicleId = $validated['transport_vehicle_id'] ?? null;
                if ($vehicleId && ! $route->vehicles()->whereKey($vehicleId)->exists()) {
                    throw ValidationException::withMessages([
                        'transport_vehicle_id' => 'The selected vehicle is not assigned to the selected route.',
                    ]);
                }

                $stopFare = $stop ? (float) $stop->fare : 0.0;
                $baseFare = $stopFare > 0 ? $stopFare : (float) $route->fare;
                if ($baseFare <= 0) {
                    throw ValidationException::withMessages([
                        'transport_route_id' => 'The selected route has no valid fare configured. Configure a positive route or stop fare before assigning transport.',
                    ]);
                }
                $waiverPct = (float) ($validated['transport_waiver_percentage'] ?? 0);
                $discountAmount = (float) ($validated['transport_discount_amount'] ?? 0);
                if (($waiverPct > 0 || $discountAmount > 0) && ! request()->user()->isAdmin()) {
                    throw ValidationException::withMessages([
                        'transport_discount_amount' => 'Only an administrator may approve transport discounts.',
                    ]);
                }
                if ($discountAmount > $baseFare) {
                    throw ValidationException::withMessages([
                        'transport_discount_amount' => 'Transport discount cannot exceed the configured fare.',
                    ]);
                }
                $waiverAmount = round($baseFare * ($waiverPct / 100), 2);
                $totalDiscount = min($baseFare, $waiverAmount + $discountAmount);
                $effectiveFare = max(0, round($baseFare - $totalDiscount, 2));

                $assignment = StudentTransportAssignment::create([
                    'student_id' => $student->id,
                    'route_id' => $route->id,
                    'stop_id' => $stop?->id,
                    'vehicle_id' => $vehicleId ?: $route->vehicles()->first()?->id,
                    'status' => 'active',
                    'start_date' => now()->toDateString(),
                    'monthly_fare' => $baseFare,
                    'waiver_percentage' => $waiverPct,
                    'discount_amount' => $discountAmount,
                    'note' => 'Assigned during student admission',
                ]);

                if ($effectiveFare > 0) {
                    FeeInvoice::create([
                        'invoice_number' => 'TRN-'.now()->format('Ymd').'-'.Str::upper(Str::random(10)),
                        'student_id' => $student->id,
                        'title' => 'Transport Fee - '.$route->route_title,
                        'category' => 'transport',
                        'amount' => $baseFare,
                        'discount' => $totalDiscount,
                        'tax' => 0,
                        'total_due' => $effectiveFare,
                        'paid_amount' => 0,
                        'status' => 'unpaid',
                        'billing_type' => 'monthly',
                        'billing_month' => now()->format('Y-m'),
                        'due_date' => now()->addDays(15)->toDateString(),
                        'notes' => 'Generated for transport assignment #'.$assignment->id,
                    ]);
                }

                TransportAuditLog::log(
                    'created', 'student_assignment', $assignment->id,
                    'Student '.$student->name.' assigned to route '.$route->route_title.' during admission.'
                );
            }
        }, 3);

        // Store optional admission documents privately after the student record commits.
        $documentUploadFailures = [];
        foreach (($validated['documents'] ?? []) as $index => $file) {
            try {
                $type = $validated['document_types'][$index] ?? 'other';
                $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');
                $filename = (string) Str::uuid().'.'.$extension;
                $path = $file->storeAs('private/student-admissions/'.$student->id, $filename, 'local');
                if (! $path) {
                    throw new \RuntimeException('Private document storage failed.');
                }
                StudentAdmissionDocument::create([
                    'student_id' => $student->id,
                    'document_type' => $type,
                    'original_filename' => mb_substr($file->getClientOriginalName(), 0, 255),
                    'file_path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'uploaded_by' => request()->user()?->id,
                ]);
            } catch (\Throwable $exception) {
                report($exception);
                $documentUploadFailures[] = $file->getClientOriginalName();
            }
        }

        // Send one-time password setup links only after the transaction commits.
        // Existing parent accounts are never reset as a side effect of admission.
        $activationFailures = [];
        foreach (array_filter([$student, $createdParent ? User::where('email', $guardianEmail)->first() : null]) as $account) {
            try {
                $status = Password::sendResetLink(['email' => $account->email]);
                if ($status !== Password::RESET_LINK_SENT) {
                    $activationFailures[] = $account->email;
                }
            } catch (\Throwable $exception) {
                report($exception);
                $activationFailures[] = $account->email;
            }
        }

        $response = redirect()->route('school.students.show', $student->id)
            ->with('success', "Student {$student->name} admitted to {$class->display_name} for {$session} with Roll No. {$student->roll_number}.");

        $warnings = [];
        if ($documentUploadFailures) {
            $warnings[] = 'Some documents could not be stored: '.implode(', ', $documentUploadFailures).'. Upload them again from the student record.';
        }
        if ($activationFailures) {
            $warnings[] = 'Account activation email could not be sent for: '.implode(', ', $activationFailures).'. Verify mail configuration and use the password-reset workflow.';
        } else {
            $response->with('status', 'Account activation links were requested for the new student and newly created parent account.');
        }
        if ($warnings) {
            $response->with('warning', 'Admission succeeded. '.implode(' ', $warnings));
        }

        return $response;
    }

    /**
     * Show detailed student file and academic records.
     */
    public function show(int $id): View
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isTeacher()), 403,
            'Unauthorized. Student profile access is reserved for faculty and administrators.');

        $student = User::query()
            ->where('role', 'student')
            ->with([
                'currentClass.classTeacher',
                'parent',
                'classEnrollments' => fn ($query) => $query->with('academicClass')->latest(),
                'reportCards' => fn ($query) => $query->where('is_published', true)->with('examTerm')->latest('generated_date'),
                'feeInvoices' => fn ($query) => $query->latest()->limit(20),
                'activeTransportAssignment.route',
                'activeTransportAssignment.stop',
                'activeTransportAssignment.vehicle.driver',
            ])
            ->findOrFail($studentId);

        // This route is authenticated and role-restricted, but keep object-level
        // authorization here so future route changes cannot expose another class.
        $this->authorizeStudentScope($student, $user);
        $admissionDocuments = StudentAdmissionDocument::where('student_id', $student->id)->latest()->get();

        // Attendance must be calculated from this student's own recorded sessions.
        // Counting all class sessions and comparing them with this student's records
        // mixed two different populations and could produce misleading percentages.
        $attendanceStats = \App\Models\AttendanceRecord::query()
            ->where('student_id', $student->id)
            ->whereIn('status', ['present', 'absent', 'late', 'half_day', 'excused'])
            ->selectRaw("COUNT(CASE WHEN status <> 'excused' THEN 1 END) as total_sessions")
            ->selectRaw("COUNT(CASE WHEN status IN ('present', 'late', 'half_day') THEN 1 END) as present_count")
            ->selectRaw("COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_count")
            ->selectRaw("COUNT(CASE WHEN status = 'excused' THEN 1 END) as excused_count")
            ->first();

        $totalSessions = (int) ($attendanceStats->total_sessions ?? 0);
        $presentCount = (int) ($attendanceStats->present_count ?? 0);
        $absentCount = (int) ($attendanceStats->absent_count ?? 0);
        $excusedCount = (int) ($attendanceStats->excused_count ?? 0);
        $attendanceRate = $totalSessions > 0
            ? round(($presentCount / $totalSessions) * 100, 1)
            : null;

        $feeSummary = FeeInvoice::query()
            ->where('student_id', $student->id)
            ->selectRaw('COALESCE(SUM(total_due), 0) as total_invoiced')
            ->selectRaw('COALESCE(SUM(paid_amount), 0) as total_paid')
            ->first();

        $totalInvoiced = (float) ($feeSummary->total_invoiced ?? 0);
        $totalPaid = (float) ($feeSummary->total_paid ?? 0);
        $totalOutstanding = max(0, $totalInvoiced - $totalPaid);

        if (request()->routeIs('students.show') || request()->is('students/*')) {
            return $this->lmsShow($student);
        }

        return view('school.students.show', compact(
            'student', 'admissionDocuments', 'totalSessions', 'presentCount', 'absentCount', 'excusedCount',
            'attendanceRate', 'totalInvoiced', 'totalPaid', 'totalOutstanding'
        ));
    }

    /**
     * Show the profile editor. Academic placement and parent linkage are deliberately
     * read-only here; they require their own audited workflows so a profile edit cannot
     * silently rewrite enrollment history or transfer a child to another guardian.
     */
    public function downloadAdmissionDocument(Request $request, int $documentId): StreamedResponse
    {
        $actor = $request->user();
        abort_unless($actor && ($actor->isAdmin() || $actor->isTeacher()), 403);
        $document = StudentAdmissionDocument::findOrFail($documentId);
        $student = User::where('role', 'student')->findOrFail($document->student_id);
        $this->authorizeStudentScope($student, $actor);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, $document->original_filename);
    }

    public function edit(int $id): View
    {
        $actor = request()->user();
        abort_unless($actor && ($actor->isAdmin() || $actor->isTeacher()), 403);

        $student = User::query()
            ->where('role', 'student')
            ->with(['currentClass', 'parent'])
            ->findOrFail($studentId);

        $this->authorizeStudentScope($student, $actor);
        $activeEnrollment = $student->classEnrollments()
            ->where('class_id', $student->current_class_id)
            ->where('status', 'active')
            ->first();

        return view('school.students.edit', compact('student', 'activeEnrollment'));
    }

    /**
     * Update editable demographic/profile fields without changing admission identity,
     * parent ownership, or academic placement. Transfers and parent-link changes must
     * use dedicated workflows with their own audit and validation rules.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor && ($actor->isAdmin() || $actor->isTeacher()), 403);

        $student = User::query()->where('role', 'student')->findOrFail($id);
        $this->authorizeStudentScope($student, $actor);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:25'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'blood_group' => ['nullable', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],
            'address' => ['nullable', 'string', 'max:255'],
            'guardian_name' => ['nullable', 'string', 'max:120'],
            'guardian_phone' => ['nullable', 'string', 'max:25'],
            'guardian_email' => ['nullable', 'email', 'max:150'],
            'previous_school_name' => ['nullable', 'string', 'max:150'],
            'previous_school_board' => ['nullable', 'string', 'max:60'],
            'previous_school_year' => ['nullable', 'string', 'max:30'],
            'previous_school_tc_number' => ['nullable', 'string', 'max:60'],
            'previous_school_remarks' => ['nullable', 'string', 'max:500'],
            'roll_number' => ['nullable', 'integer', 'min:1'],
        ]);

        // Login email changes require a separate verified account-change workflow.
        // Never switch a login identifier from the general profile editor.

        DB::transaction(function () use ($student, $validated, $actor) {
            /** @var User $lockedStudent */
            $lockedStudent = User::query()
                ->where('role', 'student')
                ->lockForUpdate()
                ->findOrFail($student->id);

            // Re-check authorization after obtaining the fresh row to avoid relying on
            // stale class placement if another request transferred the student.
            $this->authorizeStudentScope($lockedStudent, $actor);

            $rollNumber = $lockedStudent->roll_number;
            $hasRollNumberInput = array_key_exists('roll_number', $validated)
                && $validated['roll_number'] !== null;

            if ($hasRollNumberInput) {
                abort_if($lockedStudent->current_class_id === null, 422,
                    'A roll number cannot be changed until the student is enrolled in a class.');

                // Lock the class first so concurrent roll allocation/edit operations
                // for the same class serialize before checking the unique roll value.
                AcademicClass::query()
                    ->whereKey($lockedStudent->current_class_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $activeEnrollment = ClassEnrollment::query()
                    ->where('student_id', $lockedStudent->id)
                    ->where('class_id', $lockedStudent->current_class_id)
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->first();

                if (! $activeEnrollment) {
                    throw ValidationException::withMessages([
                        'roll_number' => 'The student has no active enrollment record for the current class. Resolve enrollment before editing the roll number.',
                    ]);
                }

                // Treat the active enrollment row as the canonical roll number and
                // repair a stale duplicate value on users only when saving the form.
                $currentEnrollmentRoll = (int) $activeEnrollment->roll_number;
                $requestedRoll = (int) $validated['roll_number'];

                if ($requestedRoll !== $currentEnrollmentRoll) {
                    if (! ClassEnrollment::isRollNumberAvailable(
                        (int) $lockedStudent->current_class_id,
                        $requestedRoll,
                        $lockedStudent->id
                    )) {
                        throw ValidationException::withMessages([
                            'roll_number' => 'That roll number is already assigned in this class. Choose another number.',
                        ]);
                    }

                    $activeEnrollment->update(['roll_number' => $requestedRoll]);
                }

                $rollNumber = $requestedRoll;
            }

            $changes = [
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'blood_group' => $validated['blood_group'] ?? null,
                'address' => $validated['address'] ?? null,
                'guardian_name' => $validated['guardian_name'] ?? null,
                'guardian_phone' => $validated['guardian_phone'] ?? null,
                'guardian_email' => $validated['guardian_email'] ?? null,
                'previous_school_name' => $validated['previous_school_name'] ?? null,
                'previous_school_board' => $validated['previous_school_board'] ?? null,
                'previous_school_year' => $validated['previous_school_year'] ?? null,
                'previous_school_tc_number' => $validated['previous_school_tc_number'] ?? null,
                'previous_school_remarks' => $validated['previous_school_remarks'] ?? null,
                'roll_number' => $rollNumber,
            ];

            $lockedStudent->fill($changes);
            $dirtyFields = array_keys($lockedStudent->getDirty());
            $lockedStudent->save();

            Log::info('Student profile updated', [
                'student_id' => $lockedStudent->id,
                'actor_id' => $actor->id,
                'actor_role' => $actor->role,
                'changed_fields' => $dirtyFields,
            ]);
        }, 3);

        return redirect()->route('school.students.show', $student->id)
            ->with('success', 'Student profile updated successfully. Login email, academic placement, admission identity and parent linkage were not changed.');
    }

    /**
     * Withdraw a student while preserving academic, attendance and financial history.
     */
    public function destroy(Request $request, int|User $id): RedirectResponse
    {
        $id = $id instanceof User ? $id->id : (int) $id;
        $actor = $request->user();
        abort_unless($actor, 401);
        abort_unless($actor->isAdmin(), 403, 'Only administrators may withdraw student records.');

        $validated = $request->validate([
            'confirmation_text' => ['required', 'string', 'in:WITHDRAW'],
            'admin_password' => ['required', 'string'],
            'withdrawal_reason' => ['required', 'string', 'min:10', 'max:2000'],
            'effective_date' => ['required', 'date', 'before_or_equal:today'],
        ]);

        if (! Hash::check($validated['admin_password'], $actor->password)) {
            throw ValidationException::withMessages([
                'admin_password' => 'Administrator password confirmation failed.',
            ]);
        }

        $studentName = DB::transaction(function () use ($id, $actor, $request, $validated) {
            /** @var User $student */
            $student = User::query()->where('role', 'student')->lockForUpdate()->findOrFail($id);

            if (DB::table('student_withdrawal_records')->where('student_id', $student->id)->whereNull('re_admitted_at')->exists()) {
                throw ValidationException::withMessages([
                    'student' => 'This student already has a recorded withdrawal. Use the formal re-admission workflow before any further status change.',
                ]);
            }

            // Persist an immutable offboarding record before changing operational state.
            $withdrawalRecordId = DB::table('student_withdrawal_records')->insertGetId([
                'student_id' => $student->id,
                'withdrawn_by' => $actor->id,
                'effective_date' => $validated['effective_date'],
                'reason' => trim($validated['withdrawal_reason']),
                'source' => 'admin_portal',
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 1000),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // End every active enrollment, retaining all historical enrollment rows.
            ClassEnrollment::query()
                ->where('student_id', $student->id)
                ->where('status', 'active')
                ->update([
                    'status' => 'withdrawn',
                    'ended_at' => Carbon::parse($validated['effective_date'])->startOfDay(),
                    'ended_by' => $actor->id,
                    'transition_reason' => trim($validated['withdrawal_reason']),
                    'updated_at' => now(),
                ]);

            // End transport service without deleting assignment, trip or billing history.
            StudentTransportAssignment::query()
                ->where('student_id', $student->id)
                ->where('status', 'active')
                ->update([
                    'status' => 'opted_out',
                    'end_date' => $validated['effective_date'],
                    'opt_out_reason' => substr('Student withdrawn: '.trim($validated['withdrawal_reason']), 0, 255),
                    'updated_at' => now(),
                ]);

            // Clean up uncollected future invoices so withdrawn students do not accrue false debt
            $effectiveMonth = Carbon::parse($validated['effective_date'])->format('Y-m');

            // Void/remove uncollected transport invoices from withdrawal month onwards
            FeeInvoice::query()
                ->where('student_id', $student->id)
                ->where('category', 'transport')
                ->whereIn('status', ['unpaid', 'pending'])
                ->where('paid_amount', '<=', 0)
                ->where('billing_month', '>=', $effectiveMonth)
                ->delete();

            // Void/remove uncollected tuition/general fee invoices strictly for future billing months
            FeeInvoice::query()
                ->where('student_id', $student->id)
                ->whereIn('status', ['unpaid', 'pending'])
                ->where('paid_amount', '<=', 0)
                ->where('billing_month', '>', $effectiveMonth)
                ->delete();

            // Disable login, invalidate remember-me credentials, and revoke API/browser sessions.
            $student->forceFill([
                'is_approved' => false,
                'current_class_id' => null,
                'roll_number' => null,
                'remember_token' => Str::random(60),
            ])->save();

            $student->tokens()->delete();
            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $student->id)->delete();
            }

            Log::notice('Student account withdrawn by administrator.', [
                'student_id' => $student->id,
                'actor_id' => $actor->id,
                'withdrawal_record_id' => $withdrawalRecordId,
                'effective_date' => $validated['effective_date'],
                'ip_address' => $request->ip(),
            ]);

            return $student->name;
        }, 3);

        return redirect()->route('school.students.index')
            ->with('success', "Student {$studentName} was withdrawn. Enrollment and transport were ended; academic, attendance and financial history were retained and account access was revoked.");
    }

    /**
     * Display printable official School ID Card.
     * Accessible by Faculty, Admin, or the student / student's parent.
     */
    public function idCard(int $id): View
    {
        $student = User::with(['currentClass', 'parent'])->where('role', 'student')->findOrFail($id);
        $user = Auth::user();

        abort_unless($user, 401);

        if ($user->isTeacher()) {
            $assignedClassIds = $this->getTeacherAssignedClassIds($user->id);
            abort_unless(
                $student->current_class_id !== null
                    && in_array((int) $student->current_class_id, $assignedClassIds, true),
                403,
                'Faculty members may only view ID cards of students in their assigned classes.'
            );
        } elseif ($user->isParent()) {
            abort_unless((int) $student->parent_id === (int) $user->id, 403,
                'Parents may only view their own children\'s identity cards.');
        } elseif ($user->isStudent()) {
            abort_unless((int) $user->id === (int) $student->id, 403,
                'Students may only view their own identity card.');
        } else {
            abort_unless($user->isAdmin(), 403);
        }

        $school = SchoolBrandingService::get();
        $verificationUrl = app(StudentIdCardVerificationService::class)->verificationUrl($student);

        return view('school.students.id-card', compact('student', 'school', 'verificationUrl'));
    }

    /**
     * Public cryptographic verification of an issued student identity-card URL.
     */
    public function verifyIdCard(string $hash): Response
    {
        // Public token contains only a record key and an HMAC; it carries no student PII.
        if (! preg_match('/^(\d+)\.([a-f0-9]{64})$/i', $hash, $matches)) {
            abort(404, 'Invalid or unrecognized Student Identity Verification Certificate.');
        }

        $student = User::query()
            ->where('role', 'student')
            ->with('currentClass')
            ->find((int) $matches[1]);

        $verification = app(StudentIdCardVerificationService::class);
        if (! $student || ! $verification->verifies($student, $matches[2])) {
            abort(404, 'Invalid or unrecognized Student Identity Verification Certificate.');
        }

        $isExpired = $student->card_valid_until !== null && $student->card_valid_until->isBefore(today());
        $hasActiveEnrollment = $student->current_class_id !== null
            && ClassEnrollment::query()
                ->where('student_id', $student->id)
                ->where('class_id', $student->current_class_id)
                ->where('status', 'active')
                ->exists();
        $isActive = (bool) $student->is_approved && $hasActiveEnrollment && ! $isExpired;

        $response = response()->view('school.students.verify-id', [
            'student' => $student,
            'isActive' => $isActive,
            'isExpired' => $isExpired,
            'verifiedAt' => now(),
            'school' => SchoolBrandingService::get(),
        ]);

        return $response->withHeaders([
            'Cache-Control' => 'no-store, private',
            'Referrer-Policy' => 'no-referrer',
            'X-Robots-Tag' => 'noindex, nofollow, noarchive',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * LMS Index view for student directory.
     */
    public function lmsIndex(Request $request): View
    {
        $actor = Auth::user();
        if (! $actor || (! $actor->isTeacher() && ! $actor->isAdmin())) {
            abort(403, 'Unauthorized. Student directory access is restricted to verified faculty and administrative officers.');
        }

        $allClasses = AcademicClass::orderBy('grade_level')->get();
        $allBatches = Batch::where('is_active', true)->orderBy('name')->get();

        $selectedClassId = $request->filled('class_id') ? (int) $request->query('class_id') : null;
        $selectedBatchId = $request->filled('batch_id') ? (int) $request->query('batch_id') : null;
        $searchTerm = trim($request->query('search', ''));
        $statusFilter = $request->query('status', 'all');
        $tierFilter = $request->query('tier', 'all');
        $perPage = in_array((int) $request->query('per_page', 15), [10, 15, 25, 50, 100], true)
            ? (int) $request->query('per_page', 15)
            : 15;
        $sortBy = $request->query('sort_by', 'name');
        $sortOrder = strtolower($request->query('order', 'asc')) === 'desc' ? 'desc' : 'asc';

        $query = User::where('role', 'student')
            ->with([
                'batch.academicClass',
                'classEnrollments.academicClass',
                'feeInvoices',
                'attendanceRecords',
            ]);

        if ($selectedClassId) {
            $query->where(function ($q) use ($selectedClassId) {
                $q->whereHas('classEnrollments', fn ($sub) => $sub->where('class_id', $selectedClassId))
                    ->orWhereHas('batch', fn ($sub) => $sub->where('class_id', $selectedClassId));
            });
        }

        if ($selectedBatchId) {
            $query->where('batch_id', $selectedBatchId);
        }

        if (! empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%")
                    ->orWhere('phone', 'like', "%{$searchTerm}%")
                    ->orWhere('student_id_number', 'like', "%{$searchTerm}%")
                    ->orWhere('guardian_name', 'like', "%{$searchTerm}%")
                    ->orWhere('guardian_phone', 'like', "%{$searchTerm}%")
                    ->orWhere('address', 'like', "%{$searchTerm}%")
                    ->orWhereHas('classEnrollments', fn ($sub) => $sub->where('roll_number', 'like', "%{$searchTerm}%"));
            });
        }

        if ($statusFilter === 'active') {
            $query->where('is_approved', true);
        } elseif ($statusFilter === 'pending') {
            $query->where('is_approved', false);
        }

        if ($tierFilter !== 'all' && ! empty($tierFilter)) {
            $query->where('academic_tier', $tierFilter);
        }

        if ($sortBy === 'roll_number') {
            $query->leftJoin('class_enrollments', 'users.id', '=', 'class_enrollments.student_id')
                ->orderBy('class_enrollments.roll_number', $sortOrder)
                ->select('users.*');
        } elseif (in_array($sortBy, ['name', 'email', 'xp_points', 'streak_count', 'created_at'], true)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('name', 'asc');
        }

        $students = $query->paginate($perPage)->withQueryString();

        $classCounts = [];
        $totalStudentsCount = User::where('role', 'student')->count();
        foreach ($allClasses as $c) {
            $classCounts[$c->id] = User::where('role', 'student')
                ->where(function ($q) use ($c) {
                    $q->whereHas('classEnrollments', fn ($sub) => $sub->where('class_id', $c->id))
                        ->orWhereHas('batch', fn ($sub) => $sub->where('class_id', $c->id));
                })->count();
        }

        return view('students.index', compact(
            'students',
            'allClasses',
            'allBatches',
            'selectedClassId',
            'selectedBatchId',
            'searchTerm',
            'statusFilter',
            'tierFilter',
            'perPage',
            'sortBy',
            'sortOrder',
            'classCounts',
            'totalStudentsCount'
        ));
    }

    /**
     * Detailed LMS dossier view for a student.
     */
    public function lmsShow(User $student): View
    {
        $actor = Auth::user();
        if (! $actor || (! $actor->isTeacher() && ! $actor->isAdmin())) {
            abort(403, 'Unauthorized. Student directory access is restricted.');
        }
        $this->authorizeStudentScope($student, $actor);

        $student->load([
            'batch.academicClass',
            'classEnrollments.academicClass',
            'quizAttempts.quiz',
            'attendanceRecords.session.academicClass',
        ]);

        $attendanceStats = AttendanceRecord::query()
            ->where('student_id', $student->id)
            ->selectRaw("COUNT(CASE WHEN status <> 'excused' THEN 1 END) as total_sessions")
            ->selectRaw("COUNT(CASE WHEN status IN ('present', 'late', 'half_day') THEN 1 END) as present_count")
            ->selectRaw("COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_count")
            ->selectRaw("COUNT(CASE WHEN status = 'excused' THEN 1 END) as excused_count")
            ->first();

        $totalAttendanceSessions = (int) ($attendanceStats->total_sessions ?? 0);
        $presentAttendanceCount = (int) ($attendanceStats->present_count ?? 0);
        $absentAttendanceCount = (int) ($attendanceStats->absent_count ?? 0);
        $excusedAttendanceCount = (int) ($attendanceStats->excused_count ?? 0);
        $attendanceRate = $totalAttendanceSessions > 0
            ? round(($presentAttendanceCount / $totalAttendanceSessions) * 100, 1)
            : null;

        $quizAttempts = $student->quizAttempts()->with('quiz')->latest()->take(10)->get();
        $averageQuizScore = $quizAttempts->isNotEmpty() ? round($quizAttempts->avg('score'), 1) : null;

        $invoices = $student->feeInvoices()->with('payments')->latest()->limit(20)->get();
        $feeSummary = $student->feeInvoices()
            ->selectRaw('COALESCE(SUM(total_due), 0) as total_invoiced')
            ->selectRaw('COALESCE(SUM(paid_amount), 0) as total_paid')
            ->first();
        $totalInvoiced = (float) ($feeSummary->total_invoiced ?? 0);
        $totalPaid = (float) ($feeSummary->total_paid ?? 0);
        $totalOutstanding = max(0, $totalInvoiced - $totalPaid);

        $activeEnrollment = $student->classEnrollments()->where('status', 'active')->latest()->first();
        $lastEnrollment = $student->classEnrollments()->latest()->first();

        return view('students.show', compact(
            'student',
            'activeEnrollment',
            'lastEnrollment',
            'totalAttendanceSessions',
            'presentAttendanceCount',
            'absentAttendanceCount',
            'excusedAttendanceCount',
            'attendanceRate',
            'quizAttempts',
            'averageQuizScore',
            'invoices',
            'totalInvoiced',
            'totalPaid',
            'totalOutstanding'
        ));
    }

    /**
     * Export all matching students info to CSV for download.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $actor = Auth::user();
        if (! $actor || (! $actor->isTeacher() && ! $actor->isAdmin())) {
            abort(403, 'Unauthorized.');
        }

        $selectedClassId = $request->filled('class_id') ? (int) $request->query('class_id') : null;
        $selectedBatchId = $request->filled('batch_id') ? (int) $request->query('batch_id') : null;
        $searchTerm = trim($request->query('search', ''));

        $query = User::where('role', 'student')
            ->with(['batch.academicClass', 'classEnrollments.academicClass', 'feeInvoices'])
            ->orderBy('name', 'asc');

        if ($selectedClassId) {
            $query->where(function ($q) use ($selectedClassId) {
                $q->whereHas('classEnrollments', fn ($sub) => $sub->where('class_id', $selectedClassId))
                    ->orWhereHas('batch', fn ($sub) => $sub->where('class_id', $selectedClassId));
            });
        }

        if ($selectedBatchId) {
            $query->where('batch_id', $selectedBatchId);
        }

        if (! empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%")
                    ->orWhere('phone', 'like', "%{$searchTerm}%")
                    ->orWhere('student_id_number', 'like', "%{$searchTerm}%")
                    ->orWhere('guardian_name', 'like', "%{$searchTerm}%");
            });
        }

        $students = $query->get();
        $filename = 'students_roster_'.now()->format('Y-m-d_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($students) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Student ID', 'Full Name', 'Academic Class', 'Section', 'Roll Number',
                'Batch Name', 'Batch Shift', 'Batch Timings', 'Phone Number', 'Email Address',
                'Date of Birth', 'Blood Group', 'Guardian Name', 'Guardian Phone', 'Guardian Email',
                'Residential Address', 'Academic Tier', 'XP Points', 'Streak Count', 'Approval Status', 'Card Valid Until',
            ]);

            foreach ($students as $s) {
                $enrollment = $s->classEnrollments->first();
                $academicClass = $enrollment?->academicClass ?? $s->batch?->academicClass;
                $rollNumber = $enrollment?->roll_number ?? 'N/A';

                fputcsv($handle, [
                    $s->student_id_number,
                    $s->name,
                    $academicClass?->name ?? 'Unassigned',
                    $academicClass?->section ?? 'A',
                    $rollNumber,
                    $s->batch?->name ?? 'Unassigned Batch',
                    $s->batch?->shift ?? 'N/A',
                    $s->batch?->timing_slot ?? 'N/A',
                    $s->phone ?? 'N/A',
                    $s->email,
                    $s->date_of_birth ? $s->date_of_birth->format('Y-m-d') : 'N/A',
                    $s->blood_group ?? 'N/A',
                    $s->guardian_name ?? 'N/A',
                    $s->guardian_phone ?? 'N/A',
                    $s->guardian_email ?? 'N/A',
                    $s->address ?? 'N/A',
                    $s->academic_tier ?? 'Junior Scholar',
                    $s->xp_points ?? 0,
                    $s->streak_count ?? 0,
                    $s->is_approved ? 'Active / Approved' : 'Pending Verification',
                    $s->card_valid_until ? $s->card_valid_until->format('Y-m-d') : 'N/A',
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Grab all matching student info as JSON for 1-click clipboard / data export.
     */
    public function grabJson(Request $request): JsonResponse
    {
        $actor = Auth::user();
        if (! $actor || (! $actor->isTeacher() && ! $actor->isAdmin())) {
            abort(403, 'Unauthorized.');
        }

        $selectedClassId = $request->filled('class_id') ? (int) $request->query('class_id') : null;
        $selectedBatchId = $request->filled('batch_id') ? (int) $request->query('batch_id') : null;
        $searchTerm = trim($request->query('search', ''));

        $query = User::where('role', 'student')
            ->with(['batch.academicClass', 'classEnrollments.academicClass'])
            ->orderBy('name', 'asc');

        if ($selectedClassId) {
            $query->where(function ($q) use ($selectedClassId) {
                $q->whereHas('classEnrollments', fn ($sub) => $sub->where('class_id', $selectedClassId))
                    ->orWhereHas('batch', fn ($sub) => $sub->where('class_id', $selectedClassId));
            });
        }

        if ($selectedBatchId) {
            $query->where('batch_id', $selectedBatchId);
        }

        if (! empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%")
                    ->orWhere('phone', 'like', "%{$searchTerm}%")
                    ->orWhere('student_id_number', 'like', "%{$searchTerm}%");
            });
        }

        $students = $query->get()->map(function ($s) {
            $enrollment = $s->classEnrollments->first();
            $academicClass = $enrollment?->academicClass ?? $s->batch?->academicClass;

            return [
                'id' => $s->id,
                'student_id_number' => $s->student_id_number,
                'name' => $s->name,
                'class' => $academicClass?->name ?? 'Unassigned',
                'section' => $academicClass?->section ?? 'A',
                'roll_number' => $enrollment?->roll_number ?? null,
                'batch_name' => $s->batch?->name ?? null,
                'batch_shift' => $s->batch?->shift ?? null,
                'batch_timings' => $s->batch?->timing_slot ?? null,
                'phone' => $s->phone,
                'email' => $s->email,
                'guardian_name' => $s->guardian_name,
                'guardian_phone' => $s->guardian_phone,
                'guardian_email' => $s->guardian_email,
                'address' => $s->address,
                'blood_group' => $s->blood_group,
                'date_of_birth' => $s->date_of_birth?->format('Y-m-d'),
                'academic_tier' => $s->academic_tier,
                'xp_points' => $s->xp_points,
                'streak_count' => $s->streak_count,
                'is_approved' => (bool) $s->is_approved,
            ];
        });

        return response()->json([
            'status' => 'success',
            'count' => $students->count(),
            'students' => $students,
        ]);
    }

    /**
     * Clean, printable student roster view for physical attendance or record keeping.
     */
    public function rosterPrint(Request $request): View
    {
        $actor = Auth::user();
        if (! $actor || (! $actor->isTeacher() && ! $actor->isAdmin())) {
            abort(403, 'Unauthorized.');
        }

        $selectedClassId = $request->filled('class_id') ? (int) $request->query('class_id') : null;
        $class = $selectedClassId ? AcademicClass::find($selectedClassId) : null;

        $query = User::where('role', 'student')
            ->with(['batch.academicClass', 'classEnrollments.academicClass'])
            ->orderBy('name', 'asc');

        if ($selectedClassId) {
            $query->where(function ($q) use ($selectedClassId) {
                $q->whereHas('classEnrollments', fn ($sub) => $sub->where('class_id', $selectedClassId))
                    ->orWhereHas('batch', fn ($sub) => $sub->where('class_id', $selectedClassId));
            });
        }

        $students = $query->get();

        return view('students.roster-print', compact('students', 'class', 'selectedClassId'));
    }
}
