<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Str;
use Carbon\Carbon;

class StaffPayrollComplianceController extends Controller
{
    private function admin(): void
    {
        abort_unless(Auth::user()?->isAdmin(), 403, 'Administrator access is required.');
    }

    public function index(Request $request)
    {
        $this->admin();
        $data = $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $month = $data['month'] ?? now()->format('Y-m');
        $payroll = DB::table('staff_payrolls')->where('payroll_month', $month);
        $totals = (clone $payroll)->selectRaw('COUNT(*) as payroll_count, COALESCE(SUM(gross_salary),0) as gross_total, COALESCE(SUM(net_salary),0) as net_total, COALESCE(SUM(deductions),0) as employee_deductions_total, COALESCE(SUM(employer_statutory_cost),0) as employer_statutory_total')->first();
        $liabilities = $this->liabilities($month);
        $reconciliation = DB::table('staff_payroll_reconciliations')->where('payroll_month', $month)->first();
        $remittances = DB::table('staff_statutory_remittances')->where('period_month', $month)->orderBy('rule_code')->get();
        $imports = DB::table('staff_bank_statement_imports')->orderByDesc('id')->limit(10)->get();
        $statementStart = Carbon::createFromFormat('!Y-m', $month)->startOfMonth()->toDateString();
        $statementEnd = Carbon::createFromFormat('!Y-m', $month)->endOfMonth()->toDateString();
        $bankTransactions = DB::table('staff_bank_statement_transactions as t')->join('staff_bank_statement_imports as i', 'i.id', '=', 't.import_id')->whereBetween('t.transaction_date', [$statementStart, $statementEnd])->select('t.*', 'i.original_filename', 'i.account_label')->orderByDesc('t.transaction_date')->orderByDesc('t.id')->limit(250)->get();
        $payrollCandidates = DB::table('staff_payrolls as p')->join('users as u', 'u.id', '=', 'p.staff_id')->whereBetween('p.paid_on', [$statementStart, $statementEnd])->where('p.payment_status', 'paid')->where('p.approval_status', 'approved')->select('p.id', 'u.name', 'p.payroll_month', 'p.net_salary', 'p.payment_reference')->orderBy('u.name')->get();
        $remittanceCandidates = DB::table('staff_statutory_remittances')->whereBetween('paid_on', [$statementStart, $statementEnd])->where('amount_paid', '>', 0)->orderBy('rule_code')->get();
        return view('school.payroll.compliance', compact('month', 'totals', 'liabilities', 'reconciliation', 'remittances', 'imports', 'bankTransactions', 'payrollCandidates', 'remittanceCandidates'));
    }

    public function reconcile(Request $request)
    {
        $this->admin();
        $data = $request->validate(['payroll_month' => ['required', 'date_format:Y-m'], 'notes' => ['nullable', 'string', 'max:2000']]);
        $month = $data['payroll_month'];

        DB::transaction(function () use ($month, $data) {
            $rows = DB::table('staff_payrolls')->where('payroll_month', $month)->lockForUpdate()->get();
            abort_if($rows->isEmpty(), 422, 'There are no payroll records for this month.');
            abort_if($rows->contains(fn ($row) => $row->payment_status !== 'paid' || $row->approval_status !== 'approved'), 409, 'Every payroll must be approved and marked paid before the month can be reconciled.');
            $totals = [
                'payroll_count' => $rows->count(),
                'gross_total' => round((float) $rows->sum('gross_salary'), 2),
                'net_total' => round((float) $rows->sum('net_salary'), 2),
                'employee_deductions_total' => round((float) $rows->sum('deductions'), 2),
                'employer_statutory_total' => round((float) $rows->sum('employer_statutory_cost'), 2),
                'employee_pf' => round((float) $rows->sum('employee_pf'), 2),
                'employer_pf' => round((float) $rows->sum('employer_pf'), 2),
                'employee_esi' => round((float) $rows->sum('employee_esi'), 2),
                'employer_esi' => round((float) $rows->sum('employer_esi'), 2),
                'professional_tax' => round((float) $rows->sum('professional_tax'), 2),
                'tds' => round((float) $rows->sum('tds'), 2),
            ];
            $existing = DB::table('staff_payroll_reconciliations')->where('payroll_month', $month)->lockForUpdate()->first();
            abort_if($existing, 409, 'This payroll month has already been reconciled. Reversals require a separately authorized correction workflow.');
            DB::table('staff_payroll_reconciliations')->insert([
                'payroll_month' => $month, 'payroll_count' => $totals['payroll_count'],
                'gross_total' => $totals['gross_total'], 'net_total' => $totals['net_total'],
                'employee_deductions_total' => $totals['employee_deductions_total'],
                'employer_statutory_total' => $totals['employer_statutory_total'],
                'control_totals' => json_encode($totals, JSON_THROW_ON_ERROR), 'status' => 'reconciled',
                'reconciled_by' => Auth::id(), 'reconciled_at' => now(), 'notes' => $data['notes'] ?? null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }, 3);

        return back()->with('success', 'Payroll control totals reconciled and locked for this month. This does not represent bank-statement reconciliation.');
    }

    public function createRemittance(Request $request)
    {
        $this->admin();
        $data = $request->validate([
            'rule_code' => ['required', 'in:EPF,ESI,MP_PT,TDS'],
            'period_month' => ['required', 'date_format:Y-m'],
            'due_date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $month = $data['period_month'];
        $liabilities = $this->liabilities($month);
        $amount = (float) ($liabilities[$data['rule_code']] ?? 0);
        abort_if($amount <= 0, 422, 'No positive liability was calculated for this statutory category and month.');
        abort_unless(DB::table('staff_payroll_reconciliations')->where('payroll_month', $month)->exists(), 409, 'Reconcile the fully paid payroll month before creating statutory remittance records.');
        DB::table('staff_statutory_remittances')->insertOrIgnore([
            'rule_code' => $data['rule_code'], 'jurisdiction' => $data['rule_code'] === 'MP_PT' ? 'MP' : 'IN',
            'period_month' => $month, 'amount_due' => round($amount, 2), 'amount_paid' => 0,
            'due_date' => $data['due_date'] ?? null, 'status' => 'pending', 'recorded_by' => Auth::id(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return back()->with('success', 'Statutory remittance record created if one did not already exist.');
    }

    public function recordRemittancePayment(Request $request, int $id)
    {
        $this->admin();
        $data = $request->validate([
            'amount_paid' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
            'paid_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'challan_number' => ['required', 'string', 'min:3', 'max:120'],
            'payment_reference' => ['nullable', 'string', 'max:120'],
            'variance_reason' => ['nullable', 'string', 'max:1000'],
        ]);
        DB::transaction(function () use ($id, $data) {
            $row = DB::table('staff_statutory_remittances')->where('id', $id)->lockForUpdate()->first();
            abort_unless($row, 404);
            abort_if($row->status === 'paid', 409, 'A fully paid remittance cannot be overwritten.');
            $paid = round((float) $data['amount_paid'], 2);
            $due = (float) $row->amount_due;
            abort_if(abs($paid - $due) > 0.01 && empty($data['variance_reason']), 422, 'Explain any underpayment or overpayment before recording it.');
            DB::table('staff_statutory_remittances')->where('id', $id)->update([
                'amount_paid' => $paid, 'paid_on' => $data['paid_on'], 'challan_number' => trim($data['challan_number']),
                'payment_reference' => isset($data['payment_reference']) ? trim($data['payment_reference']) : null,
                'variance_reason' => $data['variance_reason'] ?? null,
                'status' => $paid + 0.01 >= $due ? 'paid' : 'partial',
                'verified_by' => Auth::id(), 'verified_at' => now(), 'updated_at' => now(),
            ]);
        }, 3);
        return back()->with('success', 'Remittance payment evidence recorded. Verify challan details against the government portal.');
    }

    public function exportRegister(Request $request): StreamedResponse
    {
        $this->admin();
        $data = $request->validate(['month' => ['required', 'date_format:Y-m'], 'rule_code' => ['required', 'in:EPF,ESI,MP_PT,TDS']]);
        $month = $data['month'];
        $code = $data['rule_code'];
        $query = DB::table('staff_payrolls as p')->join('users as u', 'u.id', '=', 'p.staff_id')->where('p.payroll_month', $month)
            ->select('u.id as employee_internal_id', 'u.name as employee_name', 'p.payroll_month', 'p.gross_salary', 'p.pf_wages', 'p.employee_pf', 'p.employer_pf', 'p.employer_eps', 'p.edli_contribution', 'p.pf_admin_charge', 'p.employee_esi', 'p.employer_esi', 'p.professional_tax', 'p.tds', 'p.payment_status');
        $columns = match ($code) {
            'EPF' => ['employee_internal_id','employee_name','payroll_month','pf_wages','employee_pf','employer_pf','employer_eps','edli_contribution','pf_admin_charge','payment_status'],
            'ESI' => ['employee_internal_id','employee_name','payroll_month','gross_salary','employee_esi','employer_esi','payment_status'],
            'MP_PT' => ['employee_internal_id','employee_name','payroll_month','gross_salary','professional_tax','payment_status'],
            default => ['employee_internal_id','employee_name','payroll_month','gross_salary','tds','payment_status'],
        };
        return response()->streamDownload(function () use ($query, $columns) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);
            $query->orderBy('u.id')->chunk(500, function ($rows) use ($out, $columns) {
                foreach ($rows as $row) {
                    $values = [];
                    foreach ($columns as $column) {
                        $value = $row->{$column};
                        if (is_string($value) && preg_match('/^[=+@\-\t\r]/', $value)) $value = "'" . $value;
                        $values[] = $value;
                    }
                    fputcsv($out, $values);
                }
            });
            fclose($out);
        }, strtolower($code) . '-internal-register-' . $month . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }


    public function importBankStatement(Request $request)
    {
        $this->admin();
        $data = $request->validate(['statement' => ['required', 'file', 'mimes:csv,txt', 'max:10240'], 'account_label' => ['required', 'string', 'min:2', 'max:80']]);
        $file = $data['statement'];
        $path = $file->getRealPath();
        $fileHash = hash_file('sha256', $path);
        abort_if(DB::table('staff_bank_statement_imports')->where('file_sha256', $fileHash)->exists(), 409, 'This exact statement file has already been imported.');

        $handle = fopen($path, 'rb');
        abort_unless($handle, 422, 'Unable to read the uploaded statement.');
        $header = fgetcsv($handle);
        $normalize = fn ($v) => strtolower(trim(preg_replace('/^\\xEF\\xBB\\xBF/', '', (string) $v)));
        $header = array_map($normalize, is_array($header) ? $header : []);
        $required = ['transaction_date', 'description', 'reference', 'debit', 'credit'];
        abort_if(array_diff($required, $header), 422, 'CSV headers must include transaction_date, description, reference, debit, credit.');
        $positions = array_flip($header);
        $importId = null;
        $counts = ['rows' => 0, 'duplicates' => 0, 'invalid' => 0];

        DB::transaction(function () use ($handle, $file, $fileHash, $positions, $data, &$importId, &$counts) {
            $importId = DB::table('staff_bank_statement_imports')->insertGetId([
                'original_filename' => Str::limit(basename($file->getClientOriginalName()), 255, ''), 'account_label' => trim($data['account_label']),
                'file_sha256' => $fileHash, 'imported_by' => Auth::id(), 'status' => 'importing', 'created_at' => now(), 'updated_at' => now(),
            ]);
            $line = 1;
            while (($row = fgetcsv($handle)) !== false) {
                $line++;
                if ($row === [null] || count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) continue;
                try {
                    $dateRaw = trim((string) ($row[$positions['transaction_date']] ?? ''));
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateRaw)) throw new \InvalidArgumentException('Invalid date format');
                    $date = Carbon::createFromFormat('!Y-m-d', $dateRaw);
                    if (!$date || $date->format('Y-m-d') !== $dateRaw || $date->isFuture()) throw new \InvalidArgumentException('Invalid or future transaction date');
                    $description = Str::limit(trim((string) ($row[$positions['description']] ?? '')), 500, '');
                    $reference = Str::limit(trim((string) ($row[$positions['reference']] ?? '')), 160, '');
                    $debitRaw = trim((string) ($row[$positions['debit']] ?? '0'));
                    $creditRaw = trim((string) ($row[$positions['credit']] ?? '0'));
                    if (!preg_match('/^\\d{1,12}(?:\\.\\d{1,2})?$/', $debitRaw) || !preg_match('/^\\d{1,12}(?:\\.\\d{1,2})?$/', $creditRaw)) throw new \InvalidArgumentException('Invalid amount');
                    $debit = round((float) $debitRaw, 2); $credit = round((float) $creditRaw, 2);
                    if (($debit > 0) === ($credit > 0)) throw new \InvalidArgumentException('Exactly one of debit or credit must be positive');
                    $dedupeReference = $reference !== '' ? $reference : ('unreferenced-row-' . $line . '-' . $fileHash);
                    $fingerprint = hash('sha256', implode('|', [strtolower(trim($data['account_label'])), $date->toDateString(), $dedupeReference, number_format($debit, 2, '.', ''), number_format($credit, 2, '.', ''), $description]));
                    $inserted = DB::table('staff_bank_statement_transactions')->insertOrIgnore([
                        'import_id' => $importId, 'transaction_date' => $date->toDateString(), 'description' => $description ?: null,
                        'bank_reference' => $reference ?: null, 'debit' => $debit, 'credit' => $credit,
                        'transaction_fingerprint' => $fingerprint, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                    if ($inserted) $counts['rows']++; else $counts['duplicates']++;
                } catch (\Throwable $e) { $counts['invalid']++; }
                if ($line > 10001) throw new \RuntimeException('Statement exceeds the 10,000-row import limit.');
            }
            DB::table('staff_bank_statement_imports')->where('id', $importId)->update([
                'row_count' => $counts['rows'], 'duplicate_count' => $counts['duplicates'], 'invalid_count' => $counts['invalid'],
                'status' => 'imported', 'updated_at' => now(),
            ]);
            DB::table('staff_hr_audits')->insert([
                'entity_type' => 'bank_statement_import', 'entity_id' => $importId, 'actor_id' => Auth::id(), 'event_type' => 'imported',
                'before_data' => null, 'after_data' => json_encode($counts, JSON_THROW_ON_ERROR), 'created_at' => now(),
            ]);
        }, 3);
        fclose($handle);
        return back()->with('success', "Statement imported: {$counts['rows']} new rows, {$counts['duplicates']} duplicates skipped, {$counts['invalid']} invalid rows skipped.");
    }

    public function matchBankTransaction(Request $request, int $id)
    {
        $this->admin();
        $data = $request->validate([
            'match_type' => ['required', 'in:payroll,remittance'],
            'matched_entity_id' => ['required', 'integer', 'min:1'],
        ]);
        DB::transaction(function () use ($id, $data) {
            $transaction = DB::table('staff_bank_statement_transactions')->where('id', $id)->lockForUpdate()->first();
            abort_unless($transaction, 404);
            abort_if($transaction->match_type !== null, 409, 'This bank transaction has already been matched.');
            abort_if((float) $transaction->debit <= 0, 422, 'Only debit transactions can be matched to payroll or statutory payments.');

            if ($data['match_type'] === 'payroll') {
                $entity = DB::table('staff_payrolls')->where('id', $data['matched_entity_id'])->lockForUpdate()->first();
                abort_unless($entity, 404);
                abort_unless($entity->payment_status === 'paid' && $entity->approval_status === 'approved', 409, 'Only approved and recorded-paid payroll can be bank reconciled.');
                $expected = (float) $entity->net_salary;
                abort_if(abs($expected - (float) $transaction->debit) > 0.01, 422, 'Bank debit does not exactly match the payroll net amount.');
                abort_if(DB::table('staff_bank_statement_transactions')->where('match_type', 'payroll')->where('matched_entity_id', $entity->id)->exists(), 409, 'This payroll record already has a matched bank transaction.');
            } else {
                $entity = DB::table('staff_statutory_remittances')->where('id', $data['matched_entity_id'])->lockForUpdate()->first();
                abort_unless($entity, 404);
                abort_if($entity->status === 'pending', 409, 'Record the statutory payment evidence before bank matching.');
                $expected = (float) $entity->amount_paid;
                abort_if(abs($expected - (float) $transaction->debit) > 0.01, 422, 'Bank debit does not exactly match the recorded remittance payment.');
                abort_if(DB::table('staff_bank_statement_transactions')->where('match_type', 'remittance')->where('matched_entity_id', $entity->id)->exists(), 409, 'This remittance already has a matched bank transaction.');
            }
            DB::table('staff_bank_statement_transactions')->where('id', $id)->update([
                'match_type' => $data['match_type'], 'matched_entity_id' => $entity->id, 'matched_by' => Auth::id(), 'matched_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('staff_hr_audits')->insert([
                'entity_type' => 'bank_statement_transaction', 'entity_id' => $id, 'actor_id' => Auth::id(), 'event_type' => 'matched',
                'before_data' => json_encode(['match_type' => null, 'matched_entity_id' => null]),
                'after_data' => json_encode(['match_type' => $data['match_type'], 'matched_entity_id' => $entity->id]), 'created_at' => now(),
            ]);
        }, 3);
        return back()->with('success', 'Bank transaction matched. This confirms a statement amount match only; it does not verify beneficiary identity or government portal filing.');
    }


    public function unmatchBankTransaction(Request $request, int $id)
    {
        $this->admin();
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:1000']]);
        DB::transaction(function () use ($id, $data) {
            $transaction = DB::table('staff_bank_statement_transactions')->where('id', $id)->lockForUpdate()->first();
            abort_unless($transaction, 404);
            abort_if($transaction->match_type === null, 409, 'This bank transaction is not currently matched.');
            abort_if((int) $transaction->matched_by === (int) Auth::id(), 403, 'A different administrator must authorize the unmatch.');
            DB::table('staff_hr_audits')->insert([
                'entity_type' => 'bank_statement_transaction', 'entity_id' => $id, 'actor_id' => Auth::id(), 'event_type' => 'unmatched',
                'before_data' => json_encode(['match_type' => $transaction->match_type, 'matched_entity_id' => $transaction->matched_entity_id, 'matched_by' => $transaction->matched_by]),
                'after_data' => json_encode(['match_type' => null, 'matched_entity_id' => null, 'reason' => $data['reason']]), 'created_at' => now(),
            ]);
            DB::table('staff_bank_statement_transactions')->where('id', $id)->update([
                'match_type' => null, 'matched_entity_id' => null, 'matched_by' => null, 'matched_at' => null, 'updated_at' => now(),
            ]);
        }, 3);
        return back()->with('success', 'Bank match reversed with independent administrator authorization and audit reason.');
    }

    private function liabilities(string $month): array
    {
        $row = DB::table('staff_payrolls')->where('payroll_month', $month)->selectRaw(
            'COALESCE(SUM(employee_pf + employer_pf + edli_contribution + pf_admin_charge),0) as epf, COALESCE(SUM(employee_esi + employer_esi),0) as esi, COALESCE(SUM(professional_tax),0) as mp_pt, COALESCE(SUM(tds),0) as tds'
        )->first();
        return ['EPF' => (float) $row->epf, 'ESI' => (float) $row->esi, 'MP_PT' => (float) $row->mp_pt, 'TDS' => (float) $row->tds];
    }
}
