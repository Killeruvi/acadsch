<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AcademicHoliday;
use App\Models\StaffAttendanceRecord;
use App\Models\StaffLeaveRequest;
use App\Models\User;
use App\Services\HR\StaffLeaveEntitlementService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class StaffAttendanceController extends Controller
{
    private function staffUserQuery()
    {
        return User::query()->whereIn('role', ['teacher', 'admin'])->where('is_approved', true);
    }

    public function index(Request $request): View
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isTeacher()), 403);
        $month = $request->query('month', now()->format('Y-m'));
        abort_unless(is_string($month) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month), 422, 'Invalid attendance month.');
        $start = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $records = StaffAttendanceRecord::with(['staff:id,name,email', 'marker:id,name'])
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->when($user->isTeacher(), fn ($q) => $q->where('staff_id', $user->id))
            ->orderByDesc('attendance_date')->paginate(30)->withQueryString();

        $summaryQuery = StaffAttendanceRecord::whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->when($user->isTeacher(), fn ($q) => $q->where('staff_id', $user->id));
        $summary = (clone $summaryQuery)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $staff = $user->isAdmin() ? $this->staffUserQuery()->orderBy('name')->get(['id', 'name', 'role']) : collect();
        $leaves = StaffLeaveRequest::with(['staff:id,name', 'reviewer:id,name'])->when($user->isTeacher(), fn ($q) => $q->where('staff_id', $user->id))
            ->orderByDesc('created_at')->paginate(10, ['*'], 'leaves_page')->withQueryString();
        $leaveYear = (int) $request->query('leave_year', now()->year);
        abort_unless($leaveYear >= 2020 && $leaveYear <= 2100, 422);
        $balanceStaffId = $user->isAdmin() ? (int) $request->query('balance_staff_id', $user->id) : (int) $user->id;
        if ($user->isAdmin()) {
            abort_unless($this->staffUserQuery()->whereKey($balanceStaffId)->exists(), 404);
        }
        $leaveBalances = DB::table('staff_leave_balances')->where('staff_id', $balanceStaffId)->where('leave_year', $leaveYear)->orderBy('leave_type')->get()->map(function ($row) {
            $row->available = round((float) $row->opening_balance + (float) $row->accrued + (float) $row->adjusted - (float) $row->used - (float) $row->encashed, 2);

            return $row;
        });
        $leavePolicies = $user->isAdmin() ? DB::table('staff_leave_policies')->orderBy('leave_type')->orderByDesc('effective_from')->get() : collect();
        $encashments = DB::table('staff_leave_encashment_requests as e')->join('users as u', 'u.id', '=', 'e.staff_id')->leftJoin('users as r', 'r.id', '=', 'e.reviewed_by')
            ->where(function ($q) use ($user) {
                if ($user->isTeacher()) {
                    $q->where('e.staff_id', $user->id);
                }
            })
            ->select('e.*', 'u.name as staff_name', 'r.name as reviewer_name')->orderByDesc('e.created_at')->limit(50)->get();

        return view('school.staff-attendance.index', compact('records', 'summary', 'staff', 'month', 'leaves', 'leaveYear', 'balanceStaffId', 'leaveBalances', 'leavePolicies', 'encashments'));
    }

    public function mark(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
        $data = $request->validate([
            'staff_id' => ['required', 'integer', 'exists:users,id'],
            'attendance_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'status' => ['required', 'in:present,absent,half_day'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);
        $staff = $this->staffUserQuery()->whereKey($data['staff_id'])->firstOrFail();
        DB::transaction(function () use ($data, $staff) {
            $existing = StaffAttendanceRecord::where('staff_id', $staff->id)->whereDate('attendance_date', $data['attendance_date'])->lockForUpdate()->first();
            if ($existing && $existing->status === 'leave') {
                abort(409, 'Approved leave attendance cannot be overwritten through manual attendance marking.');
            }
            $payload = ['status' => $data['status'], 'remarks' => $data['remarks'] ?? null, 'marked_by' => Auth::id()];
            $record = $existing;
            $before = $record?->only(['staff_id', 'attendance_date', 'status', 'remarks', 'marked_by']);
            if ($record) {
                $record->update($payload);
            } else {
                $record = StaffAttendanceRecord::create($payload + ['staff_id' => $staff->id, 'attendance_date' => $data['attendance_date']]);
            }
            DB::table('staff_hr_audits')->insert([
                'entity_type' => 'attendance', 'entity_id' => $record->id, 'actor_id' => Auth::id(),
                'event_type' => $before ? 'corrected' : 'recorded',
                'before_data' => $before ? json_encode($before, JSON_THROW_ON_ERROR) : null,
                'after_data' => json_encode($record->fresh()->only(['staff_id', 'attendance_date', 'status', 'remarks', 'marked_by']), JSON_THROW_ON_ERROR),
                'created_at' => now(),
            ]);
        }, 3);

        return back()->with('success', 'Staff attendance saved.');
    }

    public function applyLeave(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user && in_array($user->role, ['teacher', 'admin'], true), 403);
        $data = $request->validate([
            'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'leave_type' => ['required', 'in:paid,unpaid'],
            'leave_category' => ['required', 'in:annual,casual,sick,earned,compensatory,maternity,paternity,other'],
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ]);
        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);
        abort_if($start->diffInDays($end) + 1 > (int) config('leave.max_days', 30), 422, 'Leave request exceeds the configured maximum duration.');

        DB::transaction(function () use ($user, $data) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $overlap = StaffLeaveRequest::where('staff_id', $user->id)->whereIn('status', ['pending', 'approved'])
                ->whereDate('start_date', '<=', $data['end_date'])->whereDate('end_date', '>=', $data['start_date'])->exists();
            abort_if($overlap, 409, 'A pending or approved leave request already overlaps these dates.');
            $leave = StaffLeaveRequest::create([
                'staff_id' => $user->id, 'start_date' => $data['start_date'], 'end_date' => $data['end_date'],
                'leave_type' => $data['leave_type'], 'leave_category' => $data['leave_category'], 'reason' => trim($data['reason']), 'status' => 'pending',
            ]);
            DB::table('staff_hr_audits')->insert([
                'entity_type' => 'leave', 'entity_id' => $leave->id, 'actor_id' => $user->id, 'event_type' => 'submitted',
                'before_data' => null, 'after_data' => json_encode($leave->only(['staff_id', 'start_date', 'end_date', 'leave_type', 'leave_category', 'reason', 'status']), JSON_THROW_ON_ERROR),
                'created_at' => now(),
            ]);
        }, 3);

        return back()->with('success', 'Staff leave request submitted for review.');
    }

    public function reviewLeave(Request $request, int $id, StaffLeaveEntitlementService $entitlements): RedirectResponse
    {
        $admin = Auth::user();
        abort_unless($admin?->isAdmin(), 403);
        $data = $request->validate(['status' => ['required', 'in:approved,rejected'], 'review_remarks' => ['nullable', 'string', 'max:2000']]);
        try {
            DB::transaction(function () use ($id, $admin, $data, $entitlements) {
                $leave = StaffLeaveRequest::lockForUpdate()->findOrFail($id);
                abort_if((int) $leave->staff_id === (int) $admin->id, 403, 'Administrators cannot approve or reject their own leave request.');
                abort_unless($leave->status === 'pending', 409, 'This leave request has already been reviewed.');
                $before = $leave->only(['status', 'reviewed_by', 'review_remarks']);
                $leave->update(['status' => $data['status'], 'reviewed_by' => $admin->id, 'review_remarks' => isset($data['review_remarks']) ? trim($data['review_remarks']) : null]);
                DB::table('staff_hr_audits')->insert([
                    'entity_type' => 'leave', 'entity_id' => $leave->id, 'actor_id' => $admin->id, 'event_type' => 'reviewed',
                    'before_data' => json_encode($before, JSON_THROW_ON_ERROR),
                    'after_data' => json_encode($leave->fresh()->only(['status', 'reviewed_by', 'review_remarks']), JSON_THROW_ON_ERROR),
                    'created_at' => now(),
                ]);
                if ($data['status'] === 'approved') {
                    $entitlements->consumeApprovedLeave($leave->fresh(), $admin->id);
                    $period = Carbon::parse($leave->start_date)->startOfDay();
                    $end = Carbon::parse($leave->end_date)->startOfDay()->min(Carbon::today());
                    $weekends = config('attendance.weekend_days', ['Sunday']);
                    for (; $period->lte($end); $period->addDay()) {
                        if (in_array($period->format('l'), $weekends, true) || AcademicHoliday::findHolidayForDate($period)) {
                            continue;
                        }
                        $attendance = StaffAttendanceRecord::firstOrCreate(
                            ['staff_id' => $leave->staff_id, 'attendance_date' => $period->toDateString()],
                            ['status' => 'leave', 'remarks' => ucfirst($leave->leave_type).' leave approved', 'marked_by' => $admin->id]
                        );
                        if ($attendance->wasRecentlyCreated || $attendance->status === 'absent') {
                            $beforeAttendance = $attendance->wasRecentlyCreated ? null : $attendance->only(['staff_id', 'attendance_date', 'status', 'remarks', 'marked_by']);
                            if ($beforeAttendance) {
                                $attendance->update(['status' => 'leave', 'remarks' => ucfirst($leave->leave_type).' leave approved', 'marked_by' => $admin->id]);
                            }
                            DB::table('staff_hr_audits')->insert([
                                'entity_type' => 'attendance', 'entity_id' => $attendance->id, 'actor_id' => $admin->id,
                                'event_type' => $beforeAttendance ? 'absence_excused_by_leave' : 'leave_synced',
                                'before_data' => $beforeAttendance ? json_encode($beforeAttendance, JSON_THROW_ON_ERROR) : null,
                                'after_data' => json_encode($attendance->fresh()->only(['staff_id', 'attendance_date', 'status', 'remarks', 'marked_by']), JSON_THROW_ON_ERROR),
                                'created_at' => now(),
                            ]);
                        }
                    }
                }
            }, 3);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Staff leave request reviewed.');
    }

    public function leaveBalances(Request $request)
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isTeacher()), 403);
        $staffId = $user->isAdmin() ? $request->integer('staff_id', (int) $user->id) : (int) $user->id;
        if ($user->isAdmin() && $request->filled('staff_id')) {
            abort_unless($this->staffUserQuery()->whereKey($staffId)->exists(), 404);
        }
        $year = $request->integer('year', (int) now()->year);
        abort_unless($year >= 2020 && $year <= 2100, 422);
        $balances = DB::table('staff_leave_balances')->where('staff_id', $staffId)->where('leave_year', $year)->orderBy('leave_type')->get()->map(function ($row) {
            $row->available = round((float) $row->opening_balance + (float) $row->accrued + (float) $row->adjusted - (float) $row->used - (float) $row->encashed, 2);

            return $row;
        });

        return response()->json(['staff_id' => $staffId, 'year' => $year, 'balances' => $balances]);
    }

    public function saveLeavePolicy(Request $request): RedirectResponse
    {
        $admin = Auth::user();
        abort_unless($admin?->isAdmin(), 403);
        $data = $request->validate([
            'leave_type' => ['required', 'string', 'max:40', 'regex:/^[a-z][a-z0-9_]*$/'],
            'annual_entitlement' => ['required', 'numeric', 'min:0', 'max:365'],
            'monthly_accrual' => ['required', 'numeric', 'min:0', 'max:30'],
            'maximum_carry_forward' => ['required', 'numeric', 'min:0', 'max:365'],
            'allow_encashment' => ['required', 'boolean'],
            'maximum_encashment' => ['required', 'numeric', 'min:0', 'max:365'],
            'effective_from' => ['required', 'date_format:Y-m-d'], 'effective_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:effective_from'],
            'is_enabled' => ['required', 'boolean'],
        ]);
        abort_if(! $data['allow_encashment'] && (float) $data['maximum_encashment'] > 0, 422, 'Maximum encashment must be zero when encashment is disabled.');
        DB::transaction(function () use ($data, $admin) {
            $existing = DB::table('staff_leave_policies')->where('leave_type', $data['leave_type'])->whereDate('effective_from', $data['effective_from'])->lockForUpdate()->first();
            abort_if($existing, 409, 'A policy version already exists for this leave type and effective date. Create a new version with a different effective date.');
            if ($data['is_enabled']) {
                $overlap = DB::table('staff_leave_policies')->where('leave_type', $data['leave_type'])->where('is_enabled', true)
                    ->whereDate('effective_from', '<=', $data['effective_to'] ?? '9999-12-31')
                    ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $data['effective_from']))->exists();
                abort_if($overlap, 409, 'The new enabled policy overlaps an existing enabled effective period. Close the previous period first.');
            }
            $payload = $data + ['configured_by' => $admin->id, 'created_at' => now(), 'updated_at' => now()];
            $policyId = DB::table('staff_leave_policies')->insertGetId($payload);
            DB::table('staff_hr_audits')->insert(['entity_type' => 'leave_policy', 'entity_id' => $policyId, 'actor_id' => $admin->id, 'event_type' => 'version_created', 'before_data' => null, 'after_data' => json_encode($data, JSON_THROW_ON_ERROR), 'created_at' => now()]);
        }, 3);

        return back()->with('success', 'Leave policy saved. Review the effective date and statutory/employment rules before enabling it.');
    }

    public function requestLeaveEncashment(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user && in_array($user->role, ['teacher', 'admin'], true), 403);
        $data = $request->validate([
            'leave_type' => ['required', 'string', 'max:40', 'regex:/^[a-z][a-z0-9_]*$/'],
            'leave_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'days_requested' => ['required', 'numeric', 'gt:0', 'max:365'],
            'payroll_month' => ['required', 'date_format:Y-m', 'after_or_equal:'.now()->addMonthNoOverflow()->format('Y-m')],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);
        DB::transaction(function () use ($user, $data) {
            $policy = DB::table('staff_leave_policies')->where('leave_type', $data['leave_type'])->where('is_enabled', true)
                ->whereDate('effective_from', '<=', $data['leave_year'].'-12-31')
                ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $data['leave_year'].'-01-01'))
                ->orderByDesc('effective_from')->first();
            abort_unless($policy && $policy->allow_encashment, 422, 'This leave policy does not permit encashment.');
            $balance = DB::table('staff_leave_balances')->where(['staff_id' => $user->id, 'leave_type' => $data['leave_type'], 'leave_year' => $data['leave_year']])->lockForUpdate()->first();
            abort_unless($balance, 422, 'No leave balance exists for the selected leave year.');
            $available = round((float) $balance->opening_balance + (float) $balance->accrued + (float) $balance->adjusted - (float) $balance->used - (float) $balance->encashed, 2);
            $alreadyRequested = (float) DB::table('staff_leave_encashment_requests')->where('staff_id', $user->id)->where('leave_type', $data['leave_type'])->where('leave_year', $data['leave_year'])->whereIn('status', ['pending', 'approved', 'applied'])->sum('days_requested');
            $pendingReserved = (float) DB::table('staff_leave_encashment_requests')->where('staff_id', $user->id)->where('leave_type', $data['leave_type'])->where('leave_year', $data['leave_year'])->where('status', 'pending')->sum('days_requested');
            $remainingCap = max(0, (float) $policy->maximum_encashment - $alreadyRequested);
            abort_if((float) $data['days_requested'] > max(0, $available - $pendingReserved) || (float) $data['days_requested'] > $remainingCap, 422, 'Requested days exceed the available balance or policy encashment limit.');
            $id = DB::table('staff_leave_encashment_requests')->insertGetId([
                'staff_id' => $user->id, 'leave_type' => $data['leave_type'], 'leave_year' => $data['leave_year'], 'days_requested' => $data['days_requested'],
                'payroll_month' => $data['payroll_month'], 'reason' => isset($data['reason']) ? trim($data['reason']) : null, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('staff_hr_audits')->insert(['entity_type' => 'leave_encashment', 'entity_id' => $id, 'actor_id' => $user->id, 'event_type' => 'submitted', 'before_data' => null, 'after_data' => json_encode($data, JSON_THROW_ON_ERROR), 'created_at' => now()]);
        }, 3);

        return back()->with('success', 'Leave encashment request submitted for independent review.');
    }

    public function reviewLeaveEncashment(Request $request, int $id): RedirectResponse
    {
        $admin = Auth::user();
        abort_unless($admin?->isAdmin(), 403);
        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'approved_amount' => ['required_if:decision,approve', 'nullable', 'numeric', 'gt:0', 'max:99999999.99'],
            'review_remarks' => ['nullable', 'string', 'max:2000'],
        ]);
        DB::transaction(function () use ($admin, $id, $data) {
            $item = DB::table('staff_leave_encashment_requests')->where('id', $id)->lockForUpdate()->first();
            abort_unless($item, 404);
            abort_unless($item->status === 'pending', 409, 'Only pending encashment requests can be reviewed.');
            abort_if((int) $item->staff_id === (int) $admin->id, 403, 'A different administrator must review your own encashment request.');
            if ($data['decision'] === 'approve') {
                $policy = DB::table('staff_leave_policies')->where('leave_type', $item->leave_type)->where('is_enabled', true)
                    ->whereDate('effective_from', '<=', $item->leave_year.'-12-31')->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $item->leave_year.'-01-01'))->orderByDesc('effective_from')->first();
                abort_unless($policy && $policy->allow_encashment && (float) $item->days_requested <= (float) $policy->maximum_encashment, 422, 'Current policy does not authorize this encashment.');
                $balance = DB::table('staff_leave_balances')->where(['staff_id' => $item->staff_id, 'leave_type' => $item->leave_type, 'leave_year' => $item->leave_year])->lockForUpdate()->first();
                abort_unless($balance, 404);
                $available = round((float) $balance->opening_balance + (float) $balance->accrued + (float) $balance->adjusted - (float) $balance->used - (float) $balance->encashed, 2);
                $capUsed = (float) $balance->encashed;
                abort_if($available + 0.0001 < (float) $item->days_requested || $capUsed + (float) $item->days_requested > (float) $policy->maximum_encashment, 409, 'Leave balance or encashment cap changed after submission.');
                $adjustmentId = DB::table('staff_payroll_adjustments')->insertGetId([
                    'staff_id' => $item->staff_id, 'adjustment_type' => 'leave_encashment', 'description' => 'Approved leave encashment ('.$item->days_requested.' days)',
                    'amount' => $data['approved_amount'], 'payroll_month' => $item->payroll_month, 'status' => 'approved', 'taxable' => true,
                    'submitted_by' => $item->staff_id, 'reviewed_by' => $admin->id, 'reviewed_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                ]);
                DB::table('staff_leave_balances')->where('id', $balance->id)->increment('encashed', (float) $item->days_requested);
                DB::table('staff_leave_transactions')->insert([
                    'staff_leave_balance_id' => $balance->id, 'staff_id' => $item->staff_id, 'leave_type' => $item->leave_type, 'transaction_type' => 'encashment',
                    'quantity' => $item->days_requested, 'source_type' => 'staff_leave_encashment', 'source_id' => $item->id, 'idempotency_key' => 'leave-encashment-'.$item->id,
                    'notes' => 'Approved encashment; payroll adjustment '.$adjustmentId, 'actor_id' => $admin->id, 'created_at' => now(),
                ]);
                DB::table('staff_leave_encashment_requests')->where('id', $id)->update([
                    'status' => 'approved', 'approved_amount' => $data['approved_amount'], 'payroll_adjustment_id' => $adjustmentId,
                    'reviewed_by' => $admin->id, 'reviewed_at' => now(), 'review_remarks' => $data['review_remarks'] ?? null, 'updated_at' => now(),
                ]);
            } else {
                DB::table('staff_leave_encashment_requests')->where('id', $id)->update([
                    'status' => 'rejected', 'reviewed_by' => $admin->id, 'reviewed_at' => now(), 'review_remarks' => $data['review_remarks'] ?? null, 'updated_at' => now(),
                ]);
            }
            DB::table('staff_hr_audits')->insert(['entity_type' => 'leave_encashment', 'entity_id' => $id, 'actor_id' => $admin->id, 'event_type' => $data['decision'].'d', 'before_data' => json_encode(['status' => 'pending']), 'after_data' => json_encode(['status' => $data['decision'] === 'approve' ? 'approved' : 'rejected', 'approved_amount' => $data['approved_amount'] ?? null]), 'created_at' => now()]);
        }, 3);

        return back()->with('success', 'Leave encashment review recorded. Approved amount has been queued into the selected payroll period.');
    }

    public function closeLeavePolicy(Request $request, int $id): RedirectResponse
    {
        $admin = Auth::user();
        abort_unless($admin?->isAdmin(), 403);
        $data = $request->validate(['effective_to' => ['required', 'date_format:Y-m-d']]);
        DB::transaction(function () use ($id, $data, $admin) {
            $policy = DB::table('staff_leave_policies')->where('id', $id)->lockForUpdate()->first();
            abort_unless($policy, 404);
            abort_if($policy->effective_to !== null, 409, 'This policy version is already closed.');
            abort_if($data['effective_to'] < $policy->effective_from, 422, 'Policy end date cannot precede its start date.');
            DB::table('staff_leave_policies')->where('id', $id)->update(['effective_to' => $data['effective_to'], 'updated_at' => now()]);
            DB::table('staff_hr_audits')->insert(['entity_type' => 'leave_policy', 'entity_id' => $id, 'actor_id' => $admin->id, 'event_type' => 'period_closed', 'before_data' => json_encode(['effective_to' => null]), 'after_data' => json_encode(['effective_to' => $data['effective_to']]), 'created_at' => now()]);
        }, 3);

        return back()->with('success', 'Leave policy effective period closed. A non-overlapping future version may now be created.');
    }

    public function adjustLeaveBalance(Request $request): RedirectResponse
    {
        $admin = Auth::user();
        abort_unless($admin?->isAdmin(), 403);
        $data = $request->validate([
            'staff_id' => ['required', 'integer', 'exists:users,id'], 'leave_type' => ['required', 'string', 'max:40', 'regex:/^[a-z][a-z0-9_]*$/'],
            'leave_year' => ['required', 'integer', 'min:2020', 'max:2100'], 'quantity' => ['required', 'numeric', 'not_in:0', 'between:-365,365'],
            'notes' => ['required', 'string', 'min:5', 'max:1000'],
        ]);
        abort_unless($this->staffUserQuery()->whereKey($data['staff_id'])->exists(), 404);
        DB::transaction(function () use ($data, $admin) {
            $balance = DB::table('staff_leave_balances')->where(['staff_id' => $data['staff_id'], 'leave_type' => $data['leave_type'], 'leave_year' => $data['leave_year']])->lockForUpdate()->first();
            abort_unless($balance, 404, 'Leave balance does not exist for this employee, category and year.');
            $available = round((float) $balance->opening_balance + (float) $balance->accrued + (float) $balance->adjusted - (float) $balance->used - (float) $balance->encashed, 2);
            abort_if($available + (float) $data['quantity'] < -0.0001, 422, 'This adjustment would make the leave balance negative.');
            DB::table('staff_leave_balances')->where('id', $balance->id)->increment('adjusted', (float) $data['quantity']);
            $key = 'manual-leave-adjustment-'.Str::uuid()->toString();
            DB::table('staff_leave_transactions')->insert(['staff_leave_balance_id' => $balance->id, 'staff_id' => $data['staff_id'], 'leave_type' => $data['leave_type'], 'transaction_type' => 'adjustment', 'quantity' => $data['quantity'], 'source_type' => 'manual_adjustment', 'source_id' => null, 'idempotency_key' => $key, 'notes' => trim($data['notes']), 'actor_id' => $admin->id, 'created_at' => now()]);
            DB::table('staff_hr_audits')->insert(['entity_type' => 'leave_balance', 'entity_id' => $balance->id, 'actor_id' => $admin->id, 'event_type' => 'adjusted', 'before_data' => json_encode(['available' => $available]), 'after_data' => json_encode(['quantity' => $data['quantity'], 'notes' => $data['notes'], 'available_after' => $available + (float) $data['quantity']]), 'created_at' => now()]);
        }, 3);

        return back()->with('success', 'Leave balance adjustment recorded in the append-only ledger.');
    }
}
