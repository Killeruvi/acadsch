<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AcademicClass;
use App\Models\AcademySetting;
use App\Models\Batch;
use App\Models\Course;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\SchoolFeeStructure;
use App\Models\SchoolSection;
use App\Models\User;
use App\Services\MonthlyFeeService;
use App\Services\NotificationService;
use App\Services\SchoolAcademicAuditService;
use App\Services\SchoolBrandingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SchoolFeeController extends Controller
{
    /**
     * Get class IDs assigned to teacher (homeroom, subject, or timetable).
     */
    protected function getTeacherAssignedClassIds(int $teacherId): array
    {
        return AcademicClass::where('class_teacher_id', $teacherId)
            ->orWhereHas('subjects', fn ($q) => $q->where('teacher_id', $teacherId))
            ->orWhereHas('timetables', fn ($q) => $q->where('teacher_id', $teacherId))
            ->pluck('id')
            ->toArray();
    }

    /**
     * School Fees Management Dashboard.
     */
    public function index(Request $request): View
    {
        if ($request->routeIs('fees.index') || ($request->is('fees') && ! $request->is('school/fees*'))) {
            return $this->lmsIndex($request);
        }

        $user = Auth::user();

        // 1. Student Portal: Show only own invoices and receipts
        if ($user && $user->isStudent()) {
            $classes = collect();
            $selectedClass = $user->currentClass;

            $query = FeeInvoice::with(['student.currentClass'])
                ->where('student_id', $user->id)
                ->latest();

            $invoices = $query->paginate(15)->withQueryString();

            $payments = FeePayment::with(['invoice.student.currentClass', 'receiver'])
                ->where('student_id', $user->id)
                ->latest()
                ->paginate(10, ['*'], 'payments_page')
                ->withQueryString();

            $structures = collect();

            $stats = [
                'total_billed' => FeeInvoice::where('student_id', $user->id)->sum('total_due'),
                'total_collected' => FeeInvoice::where('student_id', $user->id)->sum('paid_amount'),
                'total_pending' => FeeInvoice::where('student_id', $user->id)->whereIn('status', ['unpaid', 'partial'])->sum('total_due') - FeeInvoice::where('student_id', $user->id)->whereIn('status', ['unpaid', 'partial'])->sum('paid_amount'),
                'structures_count' => 0,
            ];

            $filter = '';
            $search = '';

            return view('school.fees.index', compact('classes', 'selectedClass', 'invoices', 'structures', 'payments', 'stats', 'filter', 'search'));
        }

        // 2. Parent Portal: Show only child's invoices and receipts
        if ($user && $user->isParent()) {
            $classes = collect();
            $children = User::childrenOf($user)
                ->with('currentClass')
                ->get();

            $selectedChildId = $request->input('child_id', session('parent_active_student_id', $children->first()?->id));
            if ($selectedChildId && $children->contains('id', (int) $selectedChildId)) {
                session(['parent_active_student_id' => (int) $selectedChildId]);
            } else {
                $selectedChildId = $children->first()?->id;
                session(['parent_active_student_id' => $selectedChildId]);
            }
            $activeChild = $children->firstWhere('id', $selectedChildId) ?? $children->first();
            $selectedClass = $activeChild ? $activeChild->currentClass : null;

            $query = FeeInvoice::with(['student.currentClass']);
            if ($activeChild) {
                $query->where('student_id', $activeChild->id);
            } else {
                $query->whereIn('student_id', $children->pluck('id'));
            }

            $invoices = $query->latest()->paginate(15)->withQueryString();
            $targetStudentIds = $activeChild ? [$activeChild->id] : $children->pluck('id')->toArray();

            $payments = FeePayment::with(['invoice.student.currentClass', 'receiver'])
                ->whereIn('student_id', $targetStudentIds)
                ->latest()
                ->paginate(10, ['*'], 'payments_page')
                ->withQueryString();

            $structures = collect();

            $stats = [
                'total_billed' => FeeInvoice::whereIn('student_id', $targetStudentIds)->sum('total_due'),
                'total_collected' => FeeInvoice::whereIn('student_id', $targetStudentIds)->sum('paid_amount'),
                'total_pending' => FeeInvoice::whereIn('student_id', $targetStudentIds)->whereIn('status', ['unpaid', 'partial'])->sum('total_due') - FeeInvoice::whereIn('student_id', $targetStudentIds)->whereIn('status', ['unpaid', 'partial'])->sum('paid_amount'),
                'structures_count' => 0,
            ];

            $filter = '';
            $search = '';

            return view('school.fees.index', compact('classes', 'selectedClass', 'invoices', 'structures', 'payments', 'stats', 'children', 'activeChild', 'filter', 'search'));
        }

        // 3. Admin & Staff: Comprehensive school fee ledger
        $classId = $request->input('class_id');
        $filter = $request->input('filter') ?? $request->input('status');
        $search = trim($request->input('search', ''));

        $classesQuery = AcademicClass::with(['feeStructures', 'students'])
            ->orderBy('grade_level')
            ->orderBy('section');

        if ($user && $user->isTeacher()) {
            $assignedClassIds = $this->getTeacherAssignedClassIds($user->id);
            $classesQuery->whereIn('id', $assignedClassIds);
        }

        $classes = $classesQuery->get();
        $selectedClass = $classId ? $classes->firstWhere('id', $classId) : null;

        // Fetch invoices with filtering and live search
        $query = FeeInvoice::with(['student.currentClass', 'payments'])
            ->latest();

        if ($filter === 'defaulters') {
            $query->where(function ($q) {
                $q->where('status', 'overdue')
                    ->orWhere(function ($sub) {
                        $sub->whereIn('status', ['unpaid', 'partial', 'partially_paid'])
                            ->where('due_date', '<', now()->toDateString());
                    });
            });
        } elseif ($filter && in_array($filter, ['unpaid', 'partial', 'paid', 'overdue'])) {
            if ($filter === 'partial') {
                $query->whereIn('status', ['partial', 'partially_paid']);
            } else {
                $query->where('status', $filter);
            }
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhereHas('student', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%")
                            ->orWhere('admission_number', 'like', "%{$search}%")
                            ->orWhere('student_id_number', 'like', "%{$search}%")
                            ->orWhere('roll_number', 'like', "%{$search}%");
                    });
            });
        }

        if ($user && $user->isTeacher()) {
            $teacherStudentIds = $classes->flatMap->students->pluck('id')->unique()->toArray();
            if ($selectedClass) {
                $studentIds = $selectedClass->students->pluck('id')->toArray();
                $query->whereIn('student_id', $studentIds);
            } else {
                $query->whereIn('student_id', $teacherStudentIds);
            }

            $invoices = $query->paginate(15)->withQueryString();
            $defaultersCount = FeeInvoice::whereIn('student_id', $teacherStudentIds)
                ->where(function ($q) {
                    $q->where('status', 'overdue')
                        ->orWhere(function ($sub) {
                            $sub->whereIn('status', ['unpaid', 'partial', 'partially_paid'])
                                ->where('due_date', '<', now()->toDateString());
                        });
                })->count();

            $stats = [
                'total_billed' => FeeInvoice::whereIn('student_id', $teacherStudentIds)->sum('total_due'),
                'total_collected' => FeeInvoice::whereIn('student_id', $teacherStudentIds)->sum('paid_amount'),
                'total_pending' => FeeInvoice::whereIn('student_id', $teacherStudentIds)->whereIn('status', ['unpaid', 'partial', 'partially_paid'])->sum('total_due') - FeeInvoice::whereIn('student_id', $teacherStudentIds)->whereIn('status', ['unpaid', 'partial', 'partially_paid'])->sum('paid_amount'),
                'structures_count' => SchoolFeeStructure::whereIn('class_id', $classes->pluck('id'))->count(),
                'defaulters_count' => $defaultersCount,
            ];
        } else {
            if ($selectedClass) {
                $studentIds = $selectedClass->students->pluck('id')->toArray();
                $query->whereIn('student_id', $studentIds);
            }

            $invoices = $query->paginate(15)->withQueryString();
            $defaultersCount = FeeInvoice::where(function ($q) {
                $q->where('status', 'overdue')
                    ->orWhere(function ($sub) {
                        $sub->whereIn('status', ['unpaid', 'partial', 'partially_paid'])
                            ->where('due_date', '<', now()->toDateString());
                    });
            })->count();

            $stats = [
                'total_billed' => FeeInvoice::sum('total_due'),
                'total_collected' => FeeInvoice::sum('paid_amount'),
                'total_pending' => FeeInvoice::whereIn('status', ['unpaid', 'partial', 'partially_paid'])->sum('total_due') - FeeInvoice::whereIn('status', ['unpaid', 'partial', 'partially_paid'])->sum('paid_amount'),
                'structures_count' => SchoolFeeStructure::count(),
                'defaulters_count' => $defaultersCount,
            ];
        }

        // Paginated class fee structures and payment transactions
        $structuresQuery = SchoolFeeStructure::with('academicClass')->orderBy('class_id');
        if ($selectedClass) {
            $structuresQuery->where('class_id', $selectedClass->id);
        }
        $structures = $structuresQuery->paginate(6, ['*'], 'structures_page')->withQueryString();

        $paymentsQuery = FeePayment::with(['invoice.student.currentClass', 'receiver'])->latest();
        if ($user && $user->isTeacher()) {
            $assignedClassIds = $this->getTeacherAssignedClassIds($user->id);
            $paymentsQuery->whereHas('invoice.student', fn ($sq) => $sq->whereIn('current_class_id', $assignedClassIds));
        } elseif ($selectedClass) {
            $paymentsQuery->whereHas('invoice.student', fn ($sq) => $sq->where('current_class_id', $selectedClass->id));
        }
        $payments = $paymentsQuery->paginate(10, ['*'], 'payments_page')->withQueryString();

        return view('school.fees.index', compact('classes', 'selectedClass', 'invoices', 'structures', 'payments', 'stats', 'filter', 'search'));
    }

    /**
     * Store a class fee structure rule.
     */
    public function storeStructure(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:academic_classes,id',
            'fee_title' => 'required|string|max:120',
            'fee_type' => 'required|string|max:40',
            'amount' => 'required|numeric|min:1',
            'due_frequency' => 'required|string|in:monthly,quarterly,annual,one_time',
            'academic_year' => 'nullable|string|max:30',
            'description' => 'nullable|string|max:255',
        ]);

        $class = AcademicClass::findOrFail($validated['class_id']);
        if (! empty($validated['academic_year']) && $validated['academic_year'] !== $class->academic_year) {
            return back()->withInput()->withErrors(['academic_year' => 'Fee structures must use the academic year configured on the selected class.']);
        }
        $validated['academic_year'] = $class->academic_year;
        SchoolFeeStructure::create($validated);

        return redirect()->back()
            ->with('success', "Fee structure '{$validated['fee_title']}' added successfully.");
    }

    /**
     * Delete a fee structure.
     */
    public function destroyStructure(int $id): RedirectResponse
    {
        $structure = SchoolFeeStructure::findOrFail($id);
        $title = $structure->fee_title;
        $structure->delete();

        return redirect()->back()
            ->with('success', "Fee structure '{$title}' was removed.");
    }

    /**
     * Batch generate monthly/term fee invoices for all enrolled students in a class.
     */
    public function generateClassInvoices(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:academic_classes,id',
            'billing_month' => 'required|string', // e.g. "2026-11"
            'due_date' => 'required|date',
            'title' => 'nullable|string|max:150',
        ]);

        $class = AcademicClass::with([
            'students' => fn ($query) => $query->wherePivot('status', 'active')->where('users.is_approved', true),
            'feeStructures',
        ])->findOrFail($validated['class_id']);

        if ($class->students->isEmpty()) {
            return redirect()->back()->with('error', 'No enrolled students found in this class to bill.');
        }

        $monthlyStructures = $class->feeStructures->where('due_frequency', 'monthly');
        $totalFeeAmount = $monthlyStructures->isNotEmpty()
            ? (float) $monthlyStructures->sum('amount')
            : (float) $class->feeStructures->sum('amount');

        if ($totalFeeAmount <= 0) {
            return redirect()->back()->with('error', 'No applicable fee structure is configured for this class. Configure the fee structure before generating invoices.');
        }

        $title = ! empty($validated['title']) ? $validated['title'] : "Monthly School Tuition & Activity Fee ({$validated['billing_month']})";
        $generatedCount = 0;

        foreach ($class->students as $student) {
            // Check if already billed for tuition for this month
            $exists = FeeInvoice::where('student_id', $student->id)
                ->where('billing_month', $validated['billing_month'])
                ->where('category', 'tuition')
                ->exists();

            if (! $exists) {
                $invoiceNo = sprintf('SCH-INV-%s-%s-%03d', date('Y'), str_replace('-', '', $validated['billing_month']), $student->id);

                FeeInvoice::create([
                    'invoice_number' => $invoiceNo,
                    'student_id' => $student->id,
                    'title' => $title,
                    'category' => 'tuition',
                    'amount' => $totalFeeAmount,
                    'discount' => 0.00,
                    'tax' => 0.00,
                    'total_due' => $totalFeeAmount,
                    'paid_amount' => 0.00,
                    'status' => 'unpaid',
                    'due_date' => $validated['due_date'],
                    'billing_type' => 'monthly',
                    'billing_month' => $validated['billing_month'],
                    'notes' => "Official school fee invoice for {$student->name} ({$class->display_name})",
                ]);
                $generatedCount++;
            }
        }

        return redirect()->back()
            ->with('success', "Generated {$generatedCount} fee invoices for {$class->display_name} for {$validated['billing_month']}!");
    }

    /**
     * Record a fee payment received (Cash / UPI / QR / Bank Transfer).
     * Only Cashier and Admin can mark a payment received.
     * Parents, Teachers, and Students have read-only access.
     */
    public function recordPayment(Request $request, int $invoiceId): RedirectResponse
    {
        $user = Auth::user();

        // Server-side role enforcement: Only Cashier and Admin can record payments.
        if (! $user || ! $user->isAdminOrCashier()) {
            abort(403, 'Unauthorized. Only Cashier and Admin staff can record fee payments.');
        }

        $invoice = FeeInvoice::with('student')->findOrFail($invoiceId);

        $balance = max(0.0, (float) $invoice->total_due - (float) $invoice->paid_amount);
        if ($balance <= 0) {
            return redirect()->back()->with('error', 'Invoice balance is already fully settled.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1|max:'.$balance,
            'payment_date' => 'required|date|before_or_equal:today',
            'payment_mode' => 'required|string|in:cash,upi,qr,bank,cheque',
            'reference_no' => 'nullable|string|max:120',
            'notes' => 'nullable|string|max:500',
        ]);

        $ref = ! empty($validated['reference_no']) ? strtoupper(trim($validated['reference_no'])) : null;
        if ($ref) {
            if (FeePayment::where('transaction_reference', $ref)->exists()) {
                return redirect()->back()->withInput()->withErrors(['reference_no' => 'This transaction reference (UTR) has already been recorded on another payment.']);
            }
        }

        $receiptNo = 'REC-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        $txnRef = $ref ?: ('TXN-'.now()->format('Ymd').'-'.strtoupper(Str::random(8)));

        DB::transaction(function () use ($invoice, $validated, $user, $receiptNo, $txnRef) {
            FeePayment::create([
                'invoice_id' => $invoice->id,
                'student_id' => $invoice->student_id,
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_mode'],
                'transaction_reference' => $txnRef,
                'receipt_number' => $receiptNo,
                'paid_at' => $validated['payment_date'],
                'status' => 'completed',
                'received_by' => $user->id,
                'notes' => $validated['notes'] ?? null,
                'payment_gateway' => 'manual',
            ]);

            $newPaid = (float) $invoice->paid_amount + (float) $validated['amount'];
            $invoice->paid_amount = $newPaid;
            $invoice->status = $newPaid >= $invoice->total_due ? 'paid' : 'partial';
            $invoice->save();
        });

        // Audit log
        try {
            app(SchoolAcademicAuditService::class)->log(
                $user,
                'fee_payment_received',
                'Payment of ₹'.number_format($validated['amount'], 2)." received for Invoice {$invoice->invoice_number}. Mode: {$validated['payment_mode']}. Receipt: {$receiptNo}.",
                null,
                ['amount' => $validated['amount'], 'mode' => $validated['payment_mode'], 'ref' => $validated['reference_no'] ?? null, 'receipt' => $receiptNo]
            );
        } catch (\Throwable $e) {
        }

        // Notify parent/student
        try {
            $studentId = $invoice->student_id;
            $student = $invoice->student;
            if ($studentId) {
                $parentId = $student?->parent_id;
                $ids = array_filter([$studentId, $parentId]);
                if (! empty($ids)) {
                    NotificationService::notifyUsers(
                        $ids,
                        'fee_payment',
                        'Fee Payment Received — ₹'.number_format($validated['amount'], 2),
                        "Receipt #{$receiptNo} generated for Invoice {$invoice->invoice_number}.",
                        route('school.fees.index'),
                        '💳'
                    );
                }
            }
        } catch (\Throwable $e) {
        }

        return redirect()->back()
            ->with('success', 'Payment of ₹'.number_format($validated['amount'], 2)." received. Receipt #{$receiptNo} generated.");
    }

    /**
     * Printable School Fee Challan / Receipt.
     */
    public function receipt(int $paymentId): View
    {
        if (request()->routeIs('fees.receipt') || request()->is('fees/receipt*')) {
            $payment = FeePayment::with(['invoice.course', 'student', 'receiver'])->findOrFail($paymentId);
            $viewer = Auth::user();
            if ($viewer->isStudent() && $payment->student_id !== $viewer->id) {
                abort(403);
            }
            if ($viewer->isParent()) {
                $student = $payment->student;
                if (! $student || (! $viewer->isGuardianOf($student))) {
                    abort(403);
                }
            }
            if (! $viewer->isAdmin() && ! $viewer->isCashier() && ! $viewer->isStudent() && ! $viewer->isParent()) {
                abort(403);
            }

            $currencySymbol = AcademySetting::get('currency_symbol', '₹');
            $instituteName = AcademySetting::get('institute_name', 'School');
            $institutionAddress = AcademySetting::get('institution_address', '');
            $bankName = AcademySetting::get('bank_name', '');
            $bankAccount = AcademySetting::get('bank_account', '');
            $bankIfsc = AcademySetting::get('bank_ifsc', '');

            return view('fees.receipt', compact(
                'payment', 'currencySymbol', 'instituteName', 'institutionAddress',
                'bankName', 'bankAccount', 'bankIfsc'
            ));
        }
        $payment = FeePayment::with(['invoice.student.currentClass'])->findOrFail($paymentId);
        if (! in_array($payment->status, ['successful', 'completed'], true)) {
            abort(404, 'An official receipt is available only after payment settlement.');
        }
        $user = Auth::user();

        if ($user && ! $user->isAdmin()) {
            if ($user->isTeacher()) {
                $assignedClassIds = $this->getTeacherAssignedClassIds($user->id);
                $studentClassId = $payment->invoice->student?->current_class_id;
                if (! $studentClassId || ! in_array($studentClassId, $assignedClassIds)) {
                    abort(403, 'Unauthorized. Faculty members may only view fee receipts for students in their assigned classes.');
                }
            }
            if ($user->isStudent() && $payment->student_id !== $user->id) {
                abort(403, 'Unauthorized. Students may only view their own receipts.');
            }
            if ($user->isParent()) {
                $student = $payment->invoice->student ?? null;
                if (! $student || (! $user->isGuardianOf($student))) {
                    abort(403, 'Unauthorized. Parents may only view receipts for their own children.');
                }
            }
        }

        return view('school.fees.receipt', compact('payment'));
    }

    /**
     * Initiate a dynamic UPI QR Code payment intent for an invoice.
     */
    /**
     * Show the school's static payment QR code for an invoice.
     * Parents and Students see this for reference when paying externally.
     * Only Cashier / Admin marks the payment received — no online checkout.
     */
    public function initiateUpiPayment(Request $request, int $invoiceId): JsonResponse
    {
        $invoice = FeeInvoice::with('student')->findOrFail($invoiceId);
        $user = Auth::user();

        // Role gate: view only for authorised parties
        if ($user && ! $user->isAdminOrCashier()) {
            if ($user->isTeacher()) {
                return response()->json(['error' => 'Faculty members cannot initiate fee transactions.'], 403);
            }
            if ($user->isStudent() && $user->id !== $invoice->student_id) {
                return response()->json(['error' => 'Unauthorized.'], 403);
            }
            if ($user->isParent() && ! $user->isGuardianOf($invoice->student)) {
                return response()->json(['error' => 'Unauthorized.'], 403);
            }
        }

        $due = max(0.0, (float) $invoice->total_due - (float) $invoice->paid_amount);
        if ($due <= 0) {
            return response()->json(['error' => 'This invoice has already been settled in full.'], 400);
        }

        $school = SchoolBrandingService::get();
        $schoolName = $school['name'] ?? config('app.name');
        // Static school QR image uploaded via Settings (not generated dynamically)
        $qrImagePath = AcademySetting::get('payment_qr_image');
        $qrImageUrl = $qrImagePath
            ? asset('storage/'.ltrim($qrImagePath, '/'))
            : null;

        return response()->json([
            'success' => true,
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'balance_due' => $due,
            'formatted_due' => '₹'.number_format($due, 2),
            'school_name' => $schoolName,
            'qr_image_url' => $qrImageUrl,
            'instructions' => 'Scan the school QR code to pay. After payment, show your UTR/reference to the cashier for confirmation.',
        ]);
    }

    /**
     * Cashier/Admin verifies a pending UPI/QR payment by entering the UTR reference.
     * This replaces webhook-based confirmation — manual, cashier-verified flow.
     */
    public function verifyUpiPayment(Request $request, int $paymentId): RedirectResponse
    {
        $user = Auth::user();
        // Only Cashier and Admin can confirm payments
        if (! $user || ! $user->isAdminOrCashier()) {
            abort(403, 'Unauthorized. Only Cashier and Admin staff can confirm payments.');
        }

        $payment = FeePayment::with(['invoice.student'])->findOrFail($paymentId);

        if ($payment->status === 'completed') {
            return redirect()->route('school.fees.receipt', $payment->id)
                ->with('info', 'This payment has already been verified and settled.');
        }

        $validated = $request->validate([
            'utr_number' => 'required|string|min:6|max:60',
        ]);

        $utr = strtoupper(trim($validated['utr_number']));

        if (FeePayment::where('transaction_reference', $utr)->where('id', '!=', $payment->id)->exists()) {
            return redirect()->back()->withInput()->withErrors(['utr_number' => 'This transaction reference (UTR) has already been recorded on another payment.']);
        }

        DB::transaction(function () use ($payment, $utr, $user) {
            $payment->update([
                'status' => 'completed',
                'transaction_reference' => $utr,
                'paid_at' => now(),
                'received_by' => $user->id,
                'notes' => "Verified by cashier. UTR: {$utr}",
                'payment_gateway' => 'manual',
            ]);

            $invoice = $payment->invoice;
            $newPaid = (float) $invoice->paid_amount + (float) $payment->amount;
            $invoice->paid_amount = $newPaid;
            $invoice->status = $newPaid >= $invoice->total_due ? 'paid' : 'partial';
            $invoice->save();
        });

        return redirect()->route('school.fees.receipt', $payment->id)
            ->with('success', 'Payment of ₹'.number_format($payment->amount, 2)." verified. Receipt #{$payment->receipt_number} generated.");
    }

    /**
     * Approve scholarship / fee waiver on an invoice.
     * Rule 1: Auto-apply discounts and scholarships only; Refund/waiver flow with approval.
     */
    public function approveWaiver(Request $request, int $invoiceId): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Unauthorized. Only administrators can approve fee discounts and waivers.');
        }

        $invoice = FeeInvoice::with('student')->findOrFail($invoiceId);
        $currentBalance = max(0.00, (float) $invoice->total_due - (float) $invoice->paid_amount);

        if ($currentBalance <= 0) {
            return redirect()->back()->with('error', 'Invoice balance is already settled or zero.');
        }

        $validated = $request->validate([
            'waiver_amount' => 'required|numeric|min:1|max:'.$currentBalance,
            'reason' => 'required|string|max:255',
        ]);

        $waiverAmount = (float) $validated['waiver_amount'];
        $newDiscount = (float) $invoice->discount + $waiverAmount;
        $newTotalDue = max(0.00, (float) $invoice->amount - $newDiscount + (float) $invoice->tax);

        $invoice->discount = $newDiscount;
        $invoice->total_due = $newTotalDue;
        if ($invoice->paid_amount >= $newTotalDue) {
            $invoice->status = 'paid';
        } elseif ($invoice->paid_amount > 0) {
            $invoice->status = 'partial';
        }

        $adminName = $user->name;
        $dateStr = now()->toDateTimeString();
        $waiverNote = ' [Approved Scholarship/Waiver of ₹'.number_format($waiverAmount, 2)." by {$adminName} on {$dateStr}. Reason: {$validated['reason']}]";
        $invoice->notes = trim(($invoice->notes ?? '').$waiverNote);
        $invoice->save();

        return redirect()->back()
            ->with('success', 'Scholarship/Waiver of ₹'.number_format($waiverAmount, 2)." approved for Invoice {$invoice->invoice_number}.");
    }

    /**
     * Process fee refund.
     * Rule 1: Refund/waiver flow with approval.
     */
    public function processRefund(Request $request, int $paymentId): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Unauthorized. Only administrators can process fee refunds.');
        }

        $payment = FeePayment::with('invoice')->findOrFail($paymentId);

        if ($payment->status === 'refunded') {
            return redirect()->back()->with('error', 'This fee payment has already been refunded.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        return DB::transaction(function () use ($payment, $validated, $user) {
            $payment = FeePayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            // Allow refund of any completed manual or QR payment (no gateway dependency)
            if (! in_array($payment->status, ['completed', 'successful'], true)) {
                return redirect()->back()->withErrors(['refund' => 'Only completed payments can be refunded.']);
            }

            $invoice = FeeInvoice::whereKey($payment->invoice_id)->lockForUpdate()->first();
            $refundAmount = (float) $payment->amount;

            $payment->update([
                'status' => 'refunded',
                'refund_reference' => 'REF-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)),
                'refund_reason' => $validated['reason'],
                'refunded_at' => now(),
                'notes' => trim(($payment->notes ?? '')." [Refunded by {$user->name} on ".now()->toDateTimeString().". Reason: {$validated['reason']}]"),
            ]);

            if ($invoice) {
                $newPaid = max(0.00, (float) $invoice->paid_amount - $refundAmount);
                $invoice->paid_amount = $newPaid;
                $invoice->status = $newPaid <= 0 ? 'unpaid' : ($newPaid < $invoice->total_due ? 'partial' : 'paid');
                $invoice->notes = trim(($invoice->notes ?? '')." [Payment #{$payment->receipt_number} of ₹".number_format($refundAmount, 2).' Refunded on '.now()->toDateString().']');
                $invoice->save();
            }

            return redirect()->back()
                ->with('success', 'Fee refund of ₹'.number_format($refundAmount, 2).' processed and ledger adjusted.');
        });
    }

    /**
     * Bulk payment recording — Cashier/Admin counter collection.
     */
    public function bulkRecordPayments(Request $request): RedirectResponse
    {
        $user = Auth::user();
        // Server-side enforcement: only Cashier and Admin
        if (! $user || ! $user->isAdminOrCashier()) {
            abort(403, 'Unauthorized. Only Cashier and Admin staff can perform bulk payment recording.');
        }

        $payments = $request->input('payments', []);
        if (is_array($payments)) {
            foreach ($payments as &$p) {
                if (is_array($p)) {
                    if (isset($p['payment_method']) && ! isset($p['payment_mode'])) {
                        $p['payment_mode'] = $p['payment_method'];
                    }
                    if (isset($p['transaction_reference']) && ! isset($p['reference_no'])) {
                        $p['reference_no'] = $p['transaction_reference'];
                    }
                    if (! isset($p['payment_date'])) {
                        $p['payment_date'] = now()->toDateString();
                    }
                }
            }
            unset($p);
            $request->merge(['payments' => $payments]);
        }

        $validated = $request->validate([
            'payments' => 'required|array|min:1',
            'payments.*.invoice_id' => 'required|exists:fee_invoices,id',
            'payments.*.amount' => 'required|numeric|min:1',
            'payments.*.payment_mode' => 'required|string|in:cash,upi,qr,bank,cheque',
            'payments.*.payment_date' => 'required|date|before_or_equal:today',
            'payments.*.reference_no' => 'nullable|string|max:120',
        ]);

        $seenRefs = [];
        foreach ($validated['payments'] as $entry) {
            $ref = ! empty($entry['reference_no']) ? strtoupper(trim($entry['reference_no'])) : null;
            if ($ref) {
                if (in_array($ref, $seenRefs, true) || FeePayment::where('transaction_reference', $ref)->exists()) {
                    return redirect()->back()->withInput()->withErrors(['payments' => "Duplicate transaction reference detected: {$ref}."]);
                }
                $seenRefs[] = $ref;
            }
        }

        $recordedCount = 0;
        $totalRecorded = 0.0;

        DB::transaction(function () use ($validated, $user, &$recordedCount, &$totalRecorded) {
            foreach ($validated['payments'] as $entry) {
                $invoice = FeeInvoice::lockForUpdate()->find($entry['invoice_id']);
                if (! $invoice) {
                    continue;
                }

                $due = max(0.00, (float) $invoice->total_due - (float) $invoice->paid_amount);
                if ($due <= 0) {
                    continue;
                }

                $amount = min($due, (float) $entry['amount']);
                $receiptNo = 'SCH-REC-'.strtoupper(Str::random(8));
                $bulkRef = ! empty($entry['reference_no']) ? $entry['reference_no'] : ('TXN-BLK-'.now()->format('Ymd').'-'.strtoupper(Str::random(8)));

                FeePayment::create([
                    'invoice_id' => $invoice->id,
                    'student_id' => $invoice->student_id,
                    'amount' => $amount,
                    'payment_method' => $entry['payment_mode'],
                    'transaction_reference' => $bulkRef,
                    'receipt_number' => $receiptNo,
                    'paid_at' => $entry['payment_date'],
                    'status' => 'completed',
                    'received_by' => $user->id,
                    'notes' => 'Bulk counter collection',
                    'payment_gateway' => 'manual',
                ]);

                $newPaid = (float) $invoice->paid_amount + $amount;
                $invoice->paid_amount = $newPaid;
                $invoice->status = $newPaid >= $invoice->total_due ? 'paid' : 'partial';
                $invoice->save();

                $recordedCount++;
                $totalRecorded += $amount;
            }
        });

        return redirect()->back()
            ->with('success', "Bulk recorded {$recordedCount} payment(s) totaling ₹".number_format($totalRecorded, 2).' successfully.');
    }

    /**
     * LMS Index - fees list & monthly fees overview.
     */
    public function lmsIndex(Request $request): View
    {
        $user = Auth::user();

        if ($user->isStudent()) {
            $totalInvoiced = FeeInvoice::where('student_id', $user->id)->sum('total_due');
            $totalPaid = FeeInvoice::where('student_id', $user->id)->sum('paid_amount');
            $totalArrears = max(0, $totalInvoiced - $totalPaid);

            $invoices = FeeInvoice::where('student_id', $user->id)
                ->with(['course', 'payments'])
                ->latest('due_date')
                ->paginate(10)
                ->withQueryString();

            $students = [];
            $courses = [];
            $classes = [];
            $batches = [];
            $monthlyConfig = [];
            $manualCount = 0;
            $monthlyAutoCount = 0;
        } else {
            // Admin / Staff view
            $query = FeeInvoice::with(['student:id,name,email,student_id_number,batch_id', 'course:id,title,code', 'payments']);

            if ($request->filled('status')) {
                $query->where('status', $request->query('status'));
            }

            if ($request->filled('student_id')) {
                $query->where('student_id', $request->query('student_id'));
            }

            if ($request->filled('billing_type')) {
                $bType = $request->query('billing_type');
                if ($bType === 'monthly_auto') {
                    $query->where('billing_type', 'monthly_auto');
                } elseif ($bType === 'manual') {
                    $query->where(function ($q) {
                        $q->whereNull('billing_type')->orWhere('billing_type', 'manual');
                    });
                }
            }

            $invoices = $query->latest('created_at')->paginate(15)->withQueryString();
            $totalInvoiced = FeeInvoice::sum('total_due');
            $totalPaid = FeeInvoice::sum('paid_amount');
            $totalArrears = max(0, $totalInvoiced - $totalPaid);
            $students = User::where('role', 'student')->orderBy('name')->get();
            $courses = Course::orderBy('title')->get();
            $classes = AcademicClass::orderBy('grade_level')->get();
            $sections = SchoolSection::where('is_active', true)->orderBy('name')->get();
            $batches = $sections;
            $monthlyConfig = MonthlyFeeService::getMonthlyFeeConfig();
            $manualCount = FeeInvoice::where(fn ($q) => $q->whereNull('billing_type')->orWhere('billing_type', 'manual'))->count();
            $monthlyAutoCount = FeeInvoice::where('billing_type', 'monthly_auto')->count();
        }

        // Paginated payment receipts & transaction ledger
        $paymentsQuery = FeePayment::with(['student', 'invoice.course'])->latest('paid_at');
        if ($user->isStudent()) {
            $paymentsQuery->where('student_id', $user->id);
        }
        $payments = $paymentsQuery->paginate(10, ['*'], 'payments_page')->withQueryString();

        $currencySymbol = AcademySetting::get('currency_symbol', '₹');
        $upiVpa = AcademySetting::get('upi_vpa', 'school@upi');
        $school = SchoolBrandingService::get();

        return view('fees.index', compact(
            'invoices',
            'payments',
            'totalInvoiced',
            'totalPaid',
            'totalArrears',
            'students',
            'courses',
            'classes',
            'sections',
            'batches',
            'monthlyConfig',
            'manualCount',
            'monthlyAutoCount',
            'currencySymbol',
            'upiVpa',
            'school'
        ));
    }

    public function showInvoice(int $id): View
    {
        $invoice = FeeInvoice::with(['student', 'course', 'payments.receiver'])->findOrFail($id);

        $viewer = Auth::user();
        if ($viewer->isStudent() && $invoice->student_id !== $viewer->id) {
            abort(403, 'Unauthorized access to this invoice.');
        }
        if ($viewer->isParent()) {
            $student = $invoice->student;
            if (! $student || (! $viewer->isGuardianOf($student))) {
                abort(403, 'Unauthorized access to this invoice.');
            }
        }
        if (! $viewer->isAdmin() && ! $viewer->isCashier() && ! $viewer->isStudent() && ! $viewer->isParent()) {
            abort(403);
        }

        $school = SchoolBrandingService::get();
        $instituteName = $school['name'] ?? AcademySetting::get('institute_name', 'School');
        $currencySymbol = AcademySetting::get('currency_symbol', '₹');
        $upiVpa = AcademySetting::get('upi_vpa', '');
        $qrImagePath = AcademySetting::get('payment_qr_image', '');
        $qrImageUrl = $qrImagePath ? asset('storage/'.$qrImagePath) : null;
        $bankName = AcademySetting::get('bank_name', '');
        $bankAccount = AcademySetting::get('bank_account', '');
        $bankIfsc = AcademySetting::get('bank_ifsc', '');

        return view('fees.show', compact('invoice', 'qrImageUrl', 'upiVpa', 'instituteName', 'currencySymbol', 'school', 'bankName', 'bankAccount', 'bankIfsc'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403, 'Only administrators can create fee invoices.');
        $validated = $request->validate([
            'student_id' => ['required', 'exists:users,id', Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', 'student'))],
            'course_id' => 'nullable|exists:courses,id',
            'title' => 'required|string|max:255',
            'category' => 'required|string|in:Tuition,Lab & Tech Fee,Exam Fee,Registration,Library',
            'amount' => 'required|numeric|min:1',
            'discount' => 'nullable|numeric|min:0|lte:amount',
            'tax' => 'nullable|numeric|min:0',
            'due_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $amount = (float) $validated['amount'];
        $discount = (float) ($validated['discount'] ?? 0);
        $tax = (float) ($validated['tax'] ?? 0);
        $totalDue = max(0, ($amount - $discount) + $tax);

        $invoiceNumber = 'INV-'.now()->format('Y').'-'.strtoupper((string) Str::ulid());

        $invoice = FeeInvoice::create([
            'invoice_number' => $invoiceNumber,
            'student_id' => $validated['student_id'],
            'course_id' => $validated['course_id'] ?? null,
            'title' => $validated['title'],
            'category' => $validated['category'],
            'amount' => $amount,
            'discount' => $discount,
            'tax' => $tax,
            'total_due' => $totalDue,
            'paid_amount' => 0.00,
            'status' => 'pending',
            'due_date' => $validated['due_date'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $currencySymbol = AcademySetting::get('currency_symbol', '₹');

        NotificationService::notifyUser(
            $invoice->student_id,
            'fee',
            "New Fee Invoice: {$invoice->invoice_number}",
            "An invoice of {$currencySymbol}{$invoice->total_due} for '{$invoice->title}' has been generated. Due: ".date('M d, Y', strtotime($invoice->due_date)),
            route('fees.show', $invoice->id),
            '💳'
        );

        return redirect()->route('fees.show', $invoice->id)
            ->with('success', "Invoice {$invoice->invoice_number} created successfully!");
    }

    /**
     * Generate recurring monthly fee invoices for enrolled students (Admin only).
     */
    public function generateMonthly(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->isAdmin()) {
            abort(403, 'Only administrators can generate monthly fee invoices.');
        }

        $validated = $request->validate([
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2020|max:2099',
            'class_id' => 'nullable|exists:academic_classes,id',
            'section_id' => 'nullable|exists:school_sections,id',
            'batch_id' => 'nullable',
            'amount' => 'nullable|numeric|min:1',
            'due_date' => 'nullable|date',
            'title' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        $sectionId = ! empty($validated['section_id']) ? (int) $validated['section_id'] : (! empty($validated['batch_id']) ? (int) $validated['batch_id'] : null);

        $result = MonthlyFeeService::generateMonthlyInvoices(
            month: ! empty($validated['month']) ? (int) $validated['month'] : null,
            year: ! empty($validated['year']) ? (int) $validated['year'] : null,
            classId: ! empty($validated['class_id']) ? (int) $validated['class_id'] : null,
            sectionId: $sectionId,
            batchId: $sectionId,
            customAmount: ! empty($validated['amount']) ? (float) $validated['amount'] : null,
            customDueDate: $validated['due_date'] ?? null,
            customTitle: $validated['title'] ?? null,
            notes: $validated['notes'] ?? null
        );

        $currencySymbol = AcademySetting::get('currency_symbol', '₹');
        $msg = "Monthly billing cycle for {$result['month_name']} {$result['year']} completed! ";
        $msg .= "Generated {$result['generated_count']} invoice(s) totaling {$currencySymbol}".number_format($result['total_amount_invoiced'], 2).'. ';
        if ($result['skipped_count'] > 0) {
            $msg .= "({$result['skipped_count']} student(s) skipped as already invoiced for this month).";
        }

        return redirect()->route('fees.index')
            ->with('success', $msg);
    }

    /**
     * Update monthly recurring fee automated configuration (Admin only).
     */
    public function updateMonthlyConfig(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->isAdmin()) {
            abort(403, 'Only administrators can update monthly fee configuration.');
        }

        $validated = $request->validate([
            'auto_enabled' => 'nullable',
            'default_amount' => 'required|numeric|min:1',
            'due_day' => 'required|integer|min:1|max:28',
            'category' => 'required|string|max:100',
            'title_template' => 'required|string|max:255',
            'notes_template' => 'nullable|string|max:500',
        ]);

        MonthlyFeeService::updateMonthlyFeeConfig([
            'auto_enabled' => $request->has('auto_enabled'),
            'default_amount' => (float) $validated['default_amount'],
            'due_day' => (int) $validated['due_day'],
            'category' => $validated['category'],
            'title_template' => $validated['title_template'],
            'notes_template' => $validated['notes_template'] ?? null,
        ]);

        return redirect()->route('fees.index')
            ->with('success', 'Monthly fee automated billing configuration updated successfully!');
    }

    /**
     * Mark a fee payment as received (Cashier + Admin only).
     */
    public function payOnline(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || (! $user->isAdmin() && ! $user->isCashier())) {
            abort(403, 'Only a Cashier or Administrator can record payments.');
        }

        $invoice = FeeInvoice::with('student')->findOrFail($id);
        $balance = round((float) $invoice->balance, 2);
        if ($balance <= 0) {
            return back()->with('info', 'This invoice is already fully paid.');
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.number_format($balance, 2, '.', '')],
            'payment_date' => 'required|date|before_or_equal:today',
            'payment_mode' => 'required|string|in:cash,upi,qr,bank,cheque',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $payAmount = round((float) $validated['amount'], 2);
        if (! is_finite($payAmount) || $payAmount <= 0 || $payAmount > $balance) {
            return back()->withErrors(['amount' => 'The payment amount is invalid or exceeds the outstanding balance.']);
        }

        $payment = DB::transaction(function () use ($invoice, $payAmount, $validated, $user) {
            $locked = FeeInvoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            if ($payAmount > round((float) $locked->balance, 2)) {
                throw ValidationException::withMessages([
                    'amount' => 'Invoice balance changed. Please refresh and try again.',
                ]);
            }
            $newPaid = round((float) $locked->paid_amount + $payAmount, 2);
            $newStatus = $newPaid >= (float) $locked->total_due ? 'paid' : 'partial';
            $receiptNo = 'REC-'.now()->format('Y').'-'.strtoupper((string) Str::ulid());
            $feeRef = ! empty($validated['reference_no']) ? $validated['reference_no'] : ('TXN-'.now()->format('Ymd').'-'.strtoupper(Str::random(8)));

            $payment = FeePayment::create([
                'invoice_id' => $locked->id,
                'student_id' => $locked->student_id,
                'amount' => $payAmount,
                'payment_mode' => $validated['payment_mode'],
                'payment_method' => strtoupper($validated['payment_mode']),
                'payment_gateway' => 'manual',
                'paid_at' => $validated['payment_date'],
                'transaction_reference' => $feeRef,
                'reference_no' => $feeRef,
                'notes' => $validated['notes'] ?? null,
                'status' => 'successful',
                'receipt_number' => $receiptNo,
                'received_by' => $user->id,
            ]);

            $locked->update(['paid_amount' => $newPaid, 'status' => $newStatus]);

            Log::info('Fee payment recorded', [
                'invoice_id' => $locked->id,
                'receipt_no' => $receiptNo,
                'amount' => $payAmount,
                'mode' => $validated['payment_mode'],
                'recorded_by' => $user->id,
            ]);

            return $payment;
        });

        $currencySymbol = AcademySetting::get('currency_symbol', '₹');
        $student = $invoice->student;

        try {
            NotificationService::notifyUser(
                $invoice->student_id, 'fee',
                "Payment Received: {$payment->receipt_number}",
                "Payment of {$currencySymbol}{$payAmount} received for '{$invoice->title}'. Receipt: {$payment->receipt_number}.",
                route('fees.receipt', $payment->id), '🧾'
            );
            if ($student && ! empty($student->parent_id)) {
                NotificationService::notifyUser(
                    $student->parent_id, 'fee',
                    "Fee Received for {$student->name}",
                    "Receipt {$payment->receipt_number} issued for {$currencySymbol}{$payAmount}.",
                    route('fees.receipt', $payment->id), '🧾'
                );
            }
        } catch (\Throwable $e) {
            Log::warning('Payment notification failed: '.$e->getMessage());
        }

        return redirect()->route('fees.receipt', $payment->id)
            ->with('success', "Payment of {$currencySymbol}{$payAmount} recorded. Receipt: {$payment->receipt_number}");
    }

    /**
     * Send payment reminder for unpaid or partial fee invoice (Admin + Cashier).
     */
    public function sendReminder(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->isAdmin() && ! $user->isCashier()) {
            abort(403, 'Only administrators or cashiers can send fee reminders.');
        }

        $invoice = FeeInvoice::with('student')->findOrFail($id);
        if ($invoice->balance <= 0) {
            return back()->with('info', 'This invoice has already been fully paid.');
        }

        $currencySymbol = AcademySetting::get('currency_symbol', '₹');
        $dueStr = $invoice->due_date ? $invoice->due_date->format('M d, Y') : 'Due Now';

        NotificationService::notifyUser(
            $invoice->student_id,
            'fee_reminder',
            "Payment Reminder: Invoice {$invoice->invoice_number}",
            "Outstanding balance of {$currencySymbol}".number_format($invoice->balance, 2)." for '{$invoice->title}' is due by {$dueStr}. Please visit the school cashier or scan the school payment QR code.",
            route('fees.show', $invoice->id),
            '⏰'
        );

        if ($invoice->student && ! empty($invoice->student->parent_id)) {
            NotificationService::notifyUser(
                $invoice->student->parent_id,
                'fee_reminder',
                "Fee Due Reminder for {$invoice->student->name}",
                "Invoice {$invoice->invoice_number} has an outstanding balance of {$currencySymbol}".number_format($invoice->balance, 2).". Due by: {$dueStr}.",
                route('fees.show', $invoice->id),
                '⏰'
            );
        }

        return back()->with('success', "Fee payment reminder sent to {$invoice->student->name} and registered guardian.");
    }

    /**
     * Delete an invoice and its payment records (Admin only).
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->isAdmin()) {
            abort(403, 'Only administrators can delete fee invoices.');
        }

        $invoice = FeeInvoice::withCount('payments')->findOrFail($id);
        if ($invoice->payments_count > 0) {
            return back()->withErrors(['invoice' => 'Invoices with payment history cannot be deleted. Record a reversal or credit note with an audit trail.']);
        }

        $invoice->delete();
        $redirectRoute = $request->routeIs('school.*') ? 'school.fees.index' : 'fees.index';

        return redirect()->route($redirectRoute)->with('success', 'Fee invoice deleted successfully.');
    }
}
