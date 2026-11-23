<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\AcademySetting;
use App\Models\Batch;
use App\Models\Course;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\User;
use App\Services\MonthlyFeeService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FeeController extends Controller
{
    public function index(Request $request): View
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
            $batches = Batch::where('is_active', true)->orderBy('name')->get();
            $monthlyConfig = MonthlyFeeService::getMonthlyFeeConfig();
            $manualCount = FeeInvoice::where(fn($q) => $q->whereNull('billing_type')->orWhere('billing_type', 'manual'))->count();
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
        $school = \App\Services\SchoolBrandingService::get();

        return view('fees.index', compact(
            'invoices',
            'payments',
            'totalInvoiced',
            'totalPaid',
            'totalArrears',
            'students',
            'courses',
            'classes',
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
        if ($viewer->isStudent() && $invoice->student_id !== $viewer->id) abort(403, 'Unauthorized access to this invoice.');
        if ($viewer->isParent()) {
            $student = $invoice->student;
            if (!$student || (!$viewer->isGuardianOf($student))) abort(403, 'Unauthorized access to this invoice.');
        }
        if (!$viewer->isAdmin() && !$viewer->isCashier() && !$viewer->isStudent() && !$viewer->isParent()) abort(403);

        $school = \App\Services\SchoolBrandingService::get();
        $instituteName = $school['name'] ?? \App\Models\AcademySetting::get('institute_name', 'School');
        $currencySymbol = \App\Models\AcademySetting::get('currency_symbol', '₹');
        $upiVpa      = \App\Models\AcademySetting::get('upi_vpa', '');
        $qrImagePath = \App\Models\AcademySetting::get('payment_qr_image', '');
        $qrImageUrl  = $qrImagePath ? asset('storage/' . $qrImagePath) : null;
        $bankName    = \App\Models\AcademySetting::get('bank_name', '');
        $bankAccount = \App\Models\AcademySetting::get('bank_account', '');
        $bankIfsc    = \App\Models\AcademySetting::get('bank_ifsc', '');

        return view('fees.show', compact('invoice', 'qrImageUrl', 'upiVpa', 'instituteName', 'currencySymbol', 'school', 'bankName', 'bankAccount', 'bankIfsc'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403, 'Only administrators can create fee invoices.');
        $validated = $request->validate([
            'student_id' => ['required', 'exists:users,id', \Illuminate\Validation\Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', 'student'))],
            'course_id' => 'nullable|exists:courses,id',
            'title' => 'required|string|max:255',
            'category' => 'required|string|in:Tuition,Lab & Tech Fee,Exam Fee,Registration,Library',
            'amount' => 'required|numeric|min:1',
            'discount' => 'nullable|numeric|min:0|lte:amount',
            'tax' => 'nullable|numeric|min:0',
            'due_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $amount = (float)$validated['amount'];
        $discount = (float)($validated['discount'] ?? 0);
        $tax = (float)($validated['tax'] ?? 0);
        $totalDue = max(0, ($amount - $discount) + $tax);

        $invoiceNumber = 'INV-' . now()->format('Y') . '-' . strtoupper((string) Str::ulid());

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

        $currencySymbol = \App\Models\AcademySetting::get('currency_symbol', '₹');

        NotificationService::notifyUser(
            $invoice->student_id,
            'fee',
            "New Fee Invoice: {$invoice->invoice_number}",
            "An invoice of {$currencySymbol}{$invoice->total_due} for '{$invoice->title}' has been generated. Due: " . date('M d, Y', strtotime($invoice->due_date)),
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
        if (!$user->isAdmin()) {
            abort(403, 'Only administrators can generate monthly fee invoices.');
        }

        $validated = $request->validate([
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2020|max:2099',
            'class_id' => 'nullable|exists:academic_classes,id',
            'batch_id' => 'nullable|exists:batches,id',
            'amount' => 'nullable|numeric|min:1',
            'due_date' => 'nullable|date',
            'title' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        $result = MonthlyFeeService::generateMonthlyInvoices(
            month: !empty($validated['month']) ? (int)$validated['month'] : null,
            year: !empty($validated['year']) ? (int)$validated['year'] : null,
            classId: !empty($validated['class_id']) ? (int)$validated['class_id'] : null,
            batchId: !empty($validated['batch_id']) ? (int)$validated['batch_id'] : null,
            customAmount: !empty($validated['amount']) ? (float)$validated['amount'] : null,
            customDueDate: $validated['due_date'] ?? null,
            customTitle: $validated['title'] ?? null,
            notes: $validated['notes'] ?? null
        );

        $currencySymbol = AcademySetting::get('currency_symbol', '₹');
        $msg = "Monthly billing cycle for {$result['month_name']} {$result['year']} completed! ";
        $msg .= "Generated {$result['generated_count']} invoice(s) totaling {$currencySymbol}" . number_format($result['total_amount_invoiced'], 2) . ". ";
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
        if (!$user->isAdmin()) {
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
            'default_amount' => (float)$validated['default_amount'],
            'due_day' => (int)$validated['due_day'],
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
        if (!$user || (!$user->isAdmin() && !$user->isCashier())) {
            abort(403, 'Only a Cashier or Administrator can record payments.');
        }

        $invoice = FeeInvoice::with('student')->findOrFail($id);
        $balance = round((float) $invoice->balance, 2);
        if ($balance <= 0) {
            return back()->with('info', 'This invoice is already fully paid.');
        }

        $validated = $request->validate([
            'amount'       => ['required', 'numeric', 'min:0.01', 'max:' . number_format($balance, 2, '.', '')],
            'payment_date' => 'required|date|before_or_equal:today',
            'payment_mode' => 'required|string|in:cash,upi,qr,bank,cheque',
            'reference_no' => 'nullable|string|max:100',
            'notes'        => 'nullable|string|max:500',
        ]);

        $payAmount = round((float) $validated['amount'], 2);
        if (!is_finite($payAmount) || $payAmount <= 0 || $payAmount > $balance) {
            return back()->withErrors(['amount' => 'The payment amount is invalid or exceeds the outstanding balance.']);
        }

        $payment = DB::transaction(function () use ($invoice, $payAmount, $validated, $user) {
            $locked = FeeInvoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            if ($payAmount > round((float) $locked->balance, 2)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'amount' => 'Invoice balance changed. Please refresh and try again.',
                ]);
            }
            $newPaid   = round((float) $locked->paid_amount + $payAmount, 2);
            $newStatus = $newPaid >= (float) $locked->total_due ? 'paid' : 'partial';
            $receiptNo = 'REC-' . now()->format('Y') . '-' . strtoupper((string) Str::ulid());
            $feeRef = !empty($validated['reference_no']) ? $validated['reference_no'] : ('TXN-' . now()->format('Ymd') . '-' . strtoupper(Str::random(8)));

            $payment = FeePayment::create([
                'invoice_id'            => $locked->id,
                'student_id'            => $locked->student_id,
                'amount'                => $payAmount,
                'payment_mode'          => $validated['payment_mode'],
                'payment_method'        => strtoupper($validated['payment_mode']),
                'payment_gateway'       => 'manual',
                'paid_at'               => $validated['payment_date'],
                'transaction_reference' => $feeRef,
                'reference_no'          => $feeRef,
                'notes'                 => $validated['notes'] ?? null,
                'status'                => 'successful',
                'receipt_number'        => $receiptNo,
                'received_by'           => $user->id,
            ]);

            $locked->update(['paid_amount' => $newPaid, 'status' => $newStatus]);

            Log::info('Fee payment recorded', [
                'invoice_id'  => $locked->id,
                'receipt_no'  => $receiptNo,
                'amount'      => $payAmount,
                'mode'        => $validated['payment_mode'],
                'recorded_by' => $user->id,
            ]);

            return $payment;
        });

        $currencySymbol = AcademySetting::get('currency_symbol', '₹');
        $student        = $invoice->student;

        try {
            NotificationService::notifyUser(
                $invoice->student_id, 'fee',
                "Payment Received: {$payment->receipt_number}",
                "Payment of {$currencySymbol}{$payAmount} received for '{$invoice->title}'. Receipt: {$payment->receipt_number}.",
                route('fees.receipt', $payment->id), '🧾'
            );
            if ($student && !empty($student->parent_id)) {
                NotificationService::notifyUser(
                    $student->parent_id, 'fee',
                    "Fee Received for {$student->name}",
                    "Receipt {$payment->receipt_number} issued for {$currencySymbol}{$payAmount}.",
                    route('fees.receipt', $payment->id), '🧾'
                );
            }
        } catch (\Throwable $e) {
            Log::warning('Payment notification failed: ' . $e->getMessage());
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
        if (!$user->isAdmin() && !$user->isCashier()) {
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
            "Outstanding balance of {$currencySymbol}" . number_format($invoice->balance, 2) . " for '{$invoice->title}' is due by {$dueStr}. Please visit the school cashier or scan the school payment QR code.",
            route('fees.show', $invoice->id),
            '⏰'
        );

        if ($invoice->student && !empty($invoice->student->parent_id)) {
            NotificationService::notifyUser(
                $invoice->student->parent_id,
                'fee_reminder',
                "Fee Due Reminder for {$invoice->student->name}",
                "Invoice {$invoice->invoice_number} has an outstanding balance of {$currencySymbol}" . number_format($invoice->balance, 2) . ". Due by: {$dueStr}.",
                route('fees.show', $invoice->id),
                '⏰'
            );
        }

        return back()->with('success', "Fee payment reminder sent to {$invoice->student->name} and registered guardian.");
    }

    public function receipt(int $paymentId): View
    {
        $payment = FeePayment::with(['invoice.course', 'student', 'receiver'])->findOrFail($paymentId);

        $viewer = Auth::user();
        if ($viewer->isStudent() && $payment->student_id !== $viewer->id) abort(403);
        if ($viewer->isParent()) {
            $student = $payment->student;
            if (!$student || (!$viewer->isGuardianOf($student))) abort(403);
        }
        if (!$viewer->isAdmin() && !$viewer->isCashier() && !$viewer->isStudent() && !$viewer->isParent()) abort(403);

        $currencySymbol   = \App\Models\AcademySetting::get('currency_symbol', '₹');
        $instituteName    = \App\Models\AcademySetting::get('institute_name', 'School');
        $institutionAddress = \App\Models\AcademySetting::get('institution_address', '');
        $bankName         = \App\Models\AcademySetting::get('bank_name', '');
        $bankAccount      = \App\Models\AcademySetting::get('bank_account', '');
        $bankIfsc         = \App\Models\AcademySetting::get('bank_ifsc', '');

        return view('fees.receipt', compact(
            'payment', 'currencySymbol', 'instituteName', 'institutionAddress',
            'bankName', 'bankAccount', 'bankIfsc'
        ));
    }

    /**
     * Delete an invoice and its payment records (Admin only).
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            abort(403, 'Only administrators can delete fee invoices.');
        }

        $invoice = FeeInvoice::withCount('payments')->findOrFail($id);
        if ($invoice->payments_count > 0) {
            return back()->withErrors(['invoice' => 'Invoices with payment history cannot be deleted. Record a reversal or credit note with an audit trail.']);
        }

        $invoice->delete();
        return redirect()->route('fees.index')->with('success', 'Fee invoice deleted successfully.');
    }
}
