<?php

namespace App\Services;

use App\Models\AcademicClass;
use App\Models\AcademySetting;
use App\Models\FeeInvoice;
use App\Models\SchoolSection;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MonthlyFeeService
{
    /**
     * Get monthly fee automation configuration settings.
     */
    public static function getMonthlyFeeConfig(): array
    {
        return [
            'auto_enabled' => AcademySetting::get('monthly_fee_auto_enabled', 'false') === 'true',
            'default_amount' => (float) AcademySetting::get('monthly_fee_default_amount', '0'),
            'due_day' => (int) AcademySetting::get('monthly_fee_due_day', '10'),
            'category' => AcademySetting::get('monthly_fee_category', 'Tuition') ?: 'Tuition',
            'title_template' => AcademySetting::get('monthly_fee_title_template', 'Monthly Tuition Fee - {month} {year}') ?: 'Monthly Tuition Fee - {month} {year}',
            'notes_template' => AcademySetting::get('monthly_fee_notes_template', 'Automated monthly tuition invoice') ?: 'Automated monthly tuition invoice',
        ];
    }

    /**
     * Update monthly fee automation configuration.
     */
    public static function updateMonthlyFeeConfig(array $data): void
    {
        if (isset($data['auto_enabled'])) {
            AcademySetting::set('monthly_fee_auto_enabled', filter_var($data['auto_enabled'], FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false');
        }
        if (isset($data['default_amount'])) {
            AcademySetting::set('monthly_fee_default_amount', (string) max(0.0, (float) $data['default_amount']));
        }
        if (isset($data['due_day'])) {
            $day = max(1, min(28, (int) $data['due_day']));
            AcademySetting::set('monthly_fee_due_day', (string) $day);
        }
        if (! empty($data['category'])) {
            AcademySetting::set('monthly_fee_category', trim($data['category']));
        }
        if (! empty($data['title_template'])) {
            AcademySetting::set('monthly_fee_title_template', trim($data['title_template']));
        }
        if (! empty($data['notes_template'])) {
            AcademySetting::set('monthly_fee_notes_template', trim($data['notes_template']));
        }
    }

    /**
     * Ensure database schema includes monthly fee columns seamlessly.
     */
    public static function ensureSchemaExists(): void
    {
        try {
            if (! Schema::hasColumn('fee_invoices', 'billing_type')) {
                Schema::table('fee_invoices', function ($table) {
                    $table->string('billing_type', 30)->default('manual')->after('status');
                });
            }
            if (! Schema::hasColumn('fee_invoices', 'billing_month')) {
                Schema::table('fee_invoices', function ($table) {
                    $table->string('billing_month', 20)->nullable()->after('billing_type');
                });
            }
        } catch (\Throwable $e) {
            // Non-blocking schema check
        }
    }

    /**
     * Generate monthly recurring fee invoices for students.
     * Idempotent: Skips students who already have an invoice for the target month.
     */
    public static function generateMonthlyInvoices(
        ?int $month = null,
        ?int $year = null,
        ?int $classId = null,
        ?int $batchId = null,
        ?float $customAmount = null,
        ?string $customDueDate = null,
        ?string $customTitle = null,
        ?string $notes = null,
        ?int $sectionId = null
    ): array {
        self::ensureSchemaExists();

        $year = $year ?: (int) date('Y');
        $month = $month ?: (int) date('n');

        if ($month < 1 || $month > 12) {
            $month = (int) date('n');
        }

        $config = self::getMonthlyFeeConfig();
        $monthDate = Carbon::createFromDate($year, $month, 1);
        $monthName = $monthDate->format('F');
        $billingMonthKey = sprintf('%04d-%02d', $year, $month);

        $amount = $customAmount !== null ? max(0.0, (float) $customAmount) : (float) $config['default_amount'];

        // Determine Due Date
        if ($customDueDate) {
            $dueDate = $customDueDate;
        } else {
            $dueDay = min(28, max(1, $config['due_day']));
            $dueDate = sprintf('%04d-%02d-%02d', $year, $month, $dueDay);
        }

        // Determine Title
        $titleTemplate = $customTitle ?: ($config['title_template'] ?: 'Monthly Tuition Fee - {month} {year}');
        $title = str_replace(
            ['{month}', '{year}'],
            [$monthName, (string) $year],
            $titleTemplate
        );
        if (empty(trim($title))) {
            $title = "Monthly Tuition Fee - {$monthName} {$year}";
        }

        $category = $config['category'] ?: 'Tuition';
        $invoiceNotes = $notes ?: ($config['notes_template']." ({$monthName} {$year})");

        // Identify Target Students
        $query = User::with(['currentClass.feeStructures', 'currentClass.schoolSection', 'parent'])
            ->where('role', 'student')
            ->where('is_approved', true);

        $targetSection = $sectionId ?? $batchId;
        if ($targetSection) {
            $query->whereHas('currentClass', function ($q) use ($targetSection) {
                $q->where('section_id', $targetSection)
                    ->orWhere('section', function ($sq) use ($targetSection) {
                        return SchoolSection::where('id', $targetSection)->select('name');
                    });
            });
        }

        if ($classId) {
            $query->where(function ($sub) use ($classId) {
                $sub->whereHas('classEnrollments', fn ($q) => $q->where('class_id', $classId))
                    ->orWhere('current_class_id', $classId);
            });
        }

        $students = $query->get();
        $currencySymbol = AcademySetting::get('currency_symbol', '₹');

        $generatedInvoices = [];
        $skippedCount = 0;
        $totalInvoicedSum = 0.00;

        DB::transaction(function () use (
            $students,
            $year,
            $monthName,
            $billingMonthKey,
            $amount,
            $customAmount,
            $title,
            $category,
            $dueDate,
            $invoiceNotes,
            $currencySymbol,
            &$generatedInvoices,
            &$skippedCount,
            &$totalInvoicedSum
        ) {
            foreach ($students as $student) {
                // Idempotency check: student already has an invoice for this billing month
                $alreadyBilled = FeeInvoice::where('student_id', $student->id)
                    ->where(function ($q) use ($billingMonthKey, $title) {
                        $q->where('billing_month', $billingMonthKey)
                            ->orWhere('title', $title);
                    })
                    ->exists();

                if ($alreadyBilled) {
                    $skippedCount++;

                    continue;
                }

                // Dynamic monthly fee calculation
                $studentClass = $student->currentClass ?? $student->batch?->academicClass ?? ($student->current_class_id ? AcademicClass::find($student->current_class_id) : null);
                $studentAmount = $amount;
                $breakdownHeads = [];

                if ($customAmount === null && $studentClass && $studentClass->feeStructures) {
                    $monthlyStructures = $studentClass->feeStructures->where('due_frequency', 'monthly');
                    if ($monthlyStructures->isEmpty()) {
                        $monthlyStructures = $studentClass->feeStructures;
                    }
                    if ($monthlyStructures->isNotEmpty() && $monthlyStructures->sum('amount') > 0) {
                        $studentAmount = (float) $monthlyStructures->sum('amount');
                        foreach ($monthlyStructures as $str) {
                            $breakdownHeads[] = [
                                'title' => $str->fee_title,
                                'type' => $str->fee_type,
                                'amount' => (float) $str->amount,
                            ];
                        }
                    }
                }

                if (empty($breakdownHeads) && $studentAmount <= 0) {
                    $skippedCount++;

                    continue;
                }
                if (empty($breakdownHeads)) {
                    $breakdownHeads[] = ['title' => $title, 'type' => $category, 'amount' => $studentAmount];
                }

                // Check prior month unpaid arrears
                $pastArrears = (float) FeeInvoice::where('student_id', $student->id)
                    ->where('billing_month', '<', $billingMonthKey)
                    ->whereIn('status', ['unpaid', 'pending', 'partial', 'overdue'])
                    ->get()
                    ->sum(fn ($inv) => max(0.00, (float) $inv->total_due - (float) $inv->paid_amount));

                $perStudentNotes = $invoiceNotes.($pastArrears > 0 ? " [Carried Forward Arrears: {$currencySymbol}".number_format($pastArrears, 2).']' : '');

                // Generate unique sequential invoice number
                $invoiceNumber = 'INV-'.$year.'-'.strtoupper((string) Str::ulid());

                $invoice = FeeInvoice::create([
                    'invoice_number' => $invoiceNumber,
                    'student_id' => $student->id,
                    'course_id' => null,
                    'title' => $title,
                    'category' => $category,
                    'amount' => $studentAmount,
                    'discount' => 0.00,
                    'tax' => 0.00,
                    'total_due' => $studentAmount,
                    'paid_amount' => 0.00,
                    'status' => 'pending',
                    'billing_type' => 'monthly_auto',
                    'billing_month' => $billingMonthKey,
                    'due_date' => $dueDate,
                    'notes' => $perStudentNotes,
                    'fee_breakdown' => [
                        'monthly_billing' => true,
                        'billing_month' => $billingMonthKey,
                        'month_name' => $monthName,
                        'year' => $year,
                        'class_id' => $studentClass?->id,
                        'class_name' => $studentClass?->display_name ?? $studentClass?->name ?? 'Standard Class',
                        'heads' => $breakdownHeads,
                        'subtotal' => $studentAmount,
                        'discount' => 0.00,
                        'base_tuition' => $studentAmount,
                        'previous_arrears' => $pastArrears,
                        'total_demand' => $studentAmount,
                    ],
                ]);

                $generatedInvoices[] = $invoice;
                $totalInvoicedSum += $studentAmount;

                // Send notification to scholar
                NotificationService::notifyUser(
                    $student->id,
                    'fee',
                    "Monthly Fee Dues: {$monthName} {$year}",
                    "Your monthly tuition invoice of {$currencySymbol}".number_format($studentAmount, 2)." for {$monthName} {$year} has been issued. Due: ".date('M d, Y', strtotime($dueDate)),
                    route('fees.show', $invoice->id),
                    '📅'
                );

                // Send notification to parent if linked
                $parent = $student->parent ?: User::where('role', 'parent')
                    ->where('email', $student->guardian_email)
                    ->first();
                if ($parent) {
                    NotificationService::notifyUser(
                        $parent->id,
                        'fee',
                        "Monthly Fee Dues: {$student->name} ({$monthName} {$year})",
                        "Monthly tuition invoice of {$currencySymbol}".number_format($studentAmount, 2)." for {$student->name} ({$monthName} {$year}) has been issued. Due: ".date('M d, Y', strtotime($dueDate)),
                        route('fees.show', $invoice->id),
                        '📅'
                    );
                }
            }
        });

        return [
            'success' => true,
            'month' => $month,
            'year' => $year,
            'month_name' => $monthName,
            'billing_month' => $billingMonthKey,
            'due_date' => $dueDate,
            'total_candidates' => $students->count(),
            'generated_count' => count($generatedInvoices),
            'skipped_count' => $skippedCount,
            'amount_per_student' => $amount,
            'total_amount_invoiced' => $totalInvoicedSum,
            'invoices' => $generatedInvoices,
        ];
    }

    /**
     * Automatically charge the current month for enrolled scholars if not yet billed.
     * Runs auto-calculation and charge without requiring manual interaction.
     */
    public static function autoChargeCurrentMonthIfDue(): array
    {
        self::ensureSchemaExists();
        $config = self::getMonthlyFeeConfig();
        if (! $config['auto_enabled']) {
            return ['status' => 'disabled', 'message' => 'Monthly fee auto-billing is disabled in School Settings.'];
        }

        $year = (int) date('Y');
        $month = (int) date('n');
        $billingMonthKey = sprintf('%04d-%02d', $year, $month);

        $hasUnbilled = User::where('role', 'student')
            ->where('is_approved', true)
            ->whereDoesntHave('feeInvoices', function ($q) use ($billingMonthKey) {
                $q->where('billing_month', $billingMonthKey);
            })
            ->exists();

        if ($hasUnbilled) {
            return self::generateMonthlyInvoices(
                month: $month,
                year: $year
            );
        }

        return ['status' => 'already_charged', 'billing_month' => $billingMonthKey];
    }
}
