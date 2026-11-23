<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StaffFinalSettlementController extends Controller
{
    private function admin(): User
    {
        $user = auth()->user();
        abort_unless($user && $user->isAdmin(), 403);

        return $user;
    }

    public function index(): View
    {
        $this->admin();
        $settlements = DB::table('staff_final_settlements as s')
            ->join('users as u', 'u.id', '=', 's.staff_id')
            ->select('s.*', 'u.name as staff_name', 'u.email as staff_email')
            ->orderByDesc('s.updated_at')->paginate(30);
        $separated = DB::table('staff_employment_profiles as p')->join('users as u', 'u.id', '=', 'p.staff_id')
            ->where('p.employment_status', 'separated')
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('staff_final_settlements as s')->whereColumn('s.staff_id', 'p.staff_id'))
            ->select('p.staff_id', 'p.employee_code', 'p.last_working_date', 'u.name')->orderByDesc('p.last_working_date')->get();

        return view('school.final-settlements.index', compact('settlements', 'separated'));
    }

    public function create(Request $request): RedirectResponse
    {
        $actor = $this->admin();
        $data = $request->validate(['staff_id' => ['required', 'integer', 'exists:users,id'], 'notes' => ['nullable', 'string', 'max:4000']]);
        DB::transaction(function () use ($data, $actor) {
            $profile = DB::table('staff_employment_profiles')->where('staff_id', $data['staff_id'])->lockForUpdate()->first();
            abort_unless($profile && $profile->employment_status === 'separated' && $profile->last_working_date, 409, 'Only separated staff with a recorded last working date can enter final settlement.');
            abort_if(DB::table('staff_final_settlements')->where('staff_id', $data['staff_id'])->exists(), 409, 'A final settlement already exists for this employee.');
            $settlementId = DB::table('staff_final_settlements')->insertGetId([
                'staff_id' => $data['staff_id'], 'last_working_date' => $profile->last_working_date,
                'status' => 'draft', 'notes' => $data['notes'] ?? null, 'created_by' => $actor->id,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            // Outstanding advances are surfaced for review, not silently treated as automatically lawful deductions.
            $outstanding = (float) DB::table('staff_salary_advances')->where('staff_id', $data['staff_id'])->where('status', 'active')
                ->selectRaw('COALESCE(SUM(GREATEST(principal_amount - repaid_amount, 0)), 0) as balance')->value('balance');
            if ($outstanding > 0) {
                DB::table('staff_final_settlement_items')->insert([
                    'settlement_id' => $settlementId, 'item_type' => 'advance_recovery',
                    'description' => 'Outstanding salary advance — verify balance and lawful recovery limit',
                    'direction' => 'deduction', 'amount' => round($outstanding, 2), 'taxable' => false,
                    'basis_reference' => 'Derived from active/approved salary advances; requires independent review',
                    'created_by' => $actor->id, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            $this->recalculate($settlementId);
            $this->audit($settlementId, $actor->id, 'created', null, ['staff_id' => $data['staff_id'], 'last_working_date' => $profile->last_working_date]);
        }, 3);

        return redirect()->route('school.staff.final-settlements.index')->with('success', 'Final settlement draft created. Add and verify all earnings and deductions before approval.');
    }

    public function addItem(Request $request, int $settlement): RedirectResponse
    {
        $actor = $this->admin();
        $data = $request->validate([
            'item_type' => ['required', 'in:salary_due,leave_encashment,gratuity,notice_pay,bonus,reimbursement,advance_recovery,tax,other'],
            'description' => ['required', 'string', 'max:180'], 'direction' => ['required', 'in:earning,deduction'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999.99'], 'taxable' => ['required', 'boolean'],
            'basis_reference' => ['nullable', 'string', 'max:240'], 'review_notes' => ['nullable', 'string', 'max:3000'],
        ]);
        DB::transaction(function () use ($settlement, $data, $actor) {
            $row = DB::table('staff_final_settlements')->where('id', $settlement)->lockForUpdate()->first();
            abort_unless($row, 404);
            abort_unless($row->status === 'draft', 409, 'Settlement items cannot change after approval.');
            DB::table('staff_final_settlement_items')->insert($data + ['settlement_id' => $settlement, 'created_by' => $actor->id, 'created_at' => now(), 'updated_at' => now()]);
            $this->recalculate($settlement);
            $this->audit($settlement, $actor->id, 'item_added', null, $data);
        }, 3);

        return back()->with('success', 'Settlement item added and totals recalculated.');
    }

    public function deleteItem(int $settlement, int $item): RedirectResponse
    {
        $actor = $this->admin();
        DB::transaction(function () use ($settlement, $item, $actor) {
            $row = DB::table('staff_final_settlements')->where('id', $settlement)->lockForUpdate()->first();
            abort_unless($row, 404);
            abort_unless($row->status === 'draft', 409, 'Only draft settlement items can be removed.');
            $itemRow = DB::table('staff_final_settlement_items')->where('id', $item)->where('settlement_id', $settlement)->first();
            abort_unless($itemRow, 404);
            DB::table('staff_final_settlement_items')->where('id', $item)->delete();
            $this->recalculate($settlement);
            $this->audit($settlement, $actor->id, 'item_removed', (array) $itemRow, null);
        }, 3);

        return back()->with('success', 'Draft settlement item removed.');
    }

    public function approve(int $settlement): RedirectResponse
    {
        $actor = $this->admin();
        DB::transaction(function () use ($settlement, $actor) {
            $row = DB::table('staff_final_settlements')->where('id', $settlement)->lockForUpdate()->first();
            abort_unless($row, 404);
            abort_unless($row->status === 'draft', 409, 'Only draft settlements can be approved.');
            abort_if((int) $row->created_by === (int) $actor->id, 403, 'A different administrator must approve this settlement.');
            $items = DB::table('staff_final_settlement_items')->where('settlement_id', $settlement)->get();
            abort_if($items->isEmpty(), 422, 'Add verified settlement items before approval.');
            abort_if($items->contains(fn ($item) => empty($item->basis_reference) && in_array($item->item_type, ['gratuity', 'notice_pay', 'leave_encashment', 'advance_recovery', 'tax'], true)), 422, 'Provide a calculation/source basis for gratuity, notice pay, leave encashment, advance recovery and tax items.');
            $totals = $this->recalculate($settlement);
            abort_if($totals['net'] < 0, 422, 'Deductions exceed earnings. Review lawful deduction limits and settlement components.');
            DB::table('staff_final_settlements')->where('id', $settlement)->update(['status' => 'approved', 'approved_by' => $actor->id, 'approved_at' => now(), 'settlement_date' => today(), 'updated_at' => now()]);
            $this->audit($settlement, $actor->id, 'approved', ['status' => 'draft'], ['status' => 'approved', 'totals' => $totals]);
        }, 3);

        return back()->with('success', 'Final settlement approved by an independent administrator. Record payment when funds are transferred.');
    }

    public function recordPayment(Request $request, int $settlement): RedirectResponse
    {
        $actor = $this->admin();
        $data = $request->validate(['amount' => ['required', 'numeric', 'gt:0', 'max:999999999999.99'], 'paid_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'], 'payment_method' => ['required', 'in:bank_transfer,cheque,upi,other'], 'payment_reference' => ['required', 'string', 'max:140']]);
        DB::transaction(function () use ($settlement, $data, $actor) {
            $row = DB::table('staff_final_settlements')->where('id', $settlement)->lockForUpdate()->first();
            abort_unless($row, 404);
            abort_unless(in_array($row->status, ['approved', 'partially_paid'], true), 409, 'Only approved settlements can receive payments.');
            $remaining = round((float) $row->net_payable - (float) $row->amount_paid, 2);
            abort_if((float) $data['amount'] > $remaining + 0.001, 422, 'Payment exceeds the outstanding settlement balance.');
            DB::table('staff_final_settlement_payments')->insert($data + ['settlement_id' => $settlement, 'recorded_by' => $actor->id, 'created_at' => now(), 'updated_at' => now()]);
            $paid = round((float) $row->amount_paid + (float) $data['amount'], 2);
            $status = $paid >= (float) $row->net_payable - 0.001 ? 'paid' : 'partially_paid';
            DB::table('staff_final_settlements')->where('id', $settlement)->update(['amount_paid' => $paid, 'status' => $status, 'updated_at' => now()]);
            $this->audit($settlement, $actor->id, 'payment_recorded', ['amount_paid' => $row->amount_paid], ['payment' => $data, 'amount_paid' => $paid, 'status' => $status]);
        }, 3);

        return back()->with('success', 'Settlement payment recorded.');
    }

    private function recalculate(int $id): array
    {
        $items = DB::table('staff_final_settlement_items')->where('settlement_id', $id)->get();
        $earnings = round((float) $items->where('direction', 'earning')->sum('amount'), 2);
        $deductions = round((float) $items->where('direction', 'deduction')->sum('amount'), 2);
        $net = round($earnings - $deductions, 2);
        DB::table('staff_final_settlements')->where('id', $id)->update(['total_earnings' => $earnings, 'total_deductions' => $deductions, 'net_payable' => $net, 'updated_at' => now()]);

        return ['earnings' => $earnings, 'deductions' => $deductions, 'net' => $net];
    }

    private function audit(int $id, int $actor, string $event, ?array $before, ?array $after): void
    {
        DB::table('staff_hr_audits')->insert(['entity_type' => 'final_settlement', 'entity_id' => $id, 'actor_id' => $actor, 'event_type' => $event,
            'before_data' => $before ? json_encode($before, JSON_THROW_ON_ERROR) : null, 'after_data' => $after ? json_encode($after, JSON_THROW_ON_ERROR) : null, 'created_at' => now()]);
    }
}
