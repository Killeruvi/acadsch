<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StudentExitController extends Controller
{
    private function admin(Request $request): User
    {
        $user = $request->user();
        abort_unless($user && $user->isAdmin(), 403, 'Administrative access is required.');
        return $user;
    }

    public function index(Request $request): View
    {
        $this->admin($request);
        $query = DB::table('student_withdrawal_records as w')
            ->join('users as u', 'u.id', '=', 'w.student_id')
            ->leftJoin('users as a', 'a.id', '=', 'w.withdrawn_by')
            ->leftJoin('student_exit_certificates as c', 'c.withdrawal_record_id', '=', 'w.id')
            ->select('w.*', 'u.name as student_name', 'u.admission_number', 'a.name as withdrawn_by_name', 'c.id as certificate_id', 'c.certificate_number')
            ->orderByDesc('w.effective_date')->orderByDesc('w.id');
        if ($request->filled('search')) {
            $term = trim((string) $request->query('search'));
            $query->where(function ($q) use ($term) {
                $q->where('u.name', 'like', '%' . $term . '%')->orWhere('u.admission_number', 'like', '%' . $term . '%');
            });
        }
        $records = $query->paginate(25)->withQueryString();
        return view('school.student-exits.index', compact('records'));
    }

    public function issue(Request $request, int $withdrawalId)
    {
        $actor = $this->admin($request);
        $data = $request->validate([
            'date_of_issue' => ['required', 'date'],
            'conduct' => ['nullable', 'string', 'max:100'],
            'last_class_text' => ['nullable', 'string', 'max:100'],
            'next_institution' => ['nullable', 'string', 'max:190'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $certificateId = DB::transaction(function () use ($withdrawalId, $actor, $data) {
            $withdrawal = DB::table('student_withdrawal_records')->where('id', $withdrawalId)->lockForUpdate()->first();
            if (!$withdrawal || $withdrawal->re_admitted_at !== null) {
                throw ValidationException::withMessages(['withdrawal' => 'A transfer certificate can only be issued for a currently withdrawn student.']);
            }
            $existing = DB::table('student_exit_certificates')->where('withdrawal_record_id', $withdrawalId)->first();
            if ($existing) {
                throw ValidationException::withMessages(['withdrawal' => 'A transfer certificate has already been issued for this withdrawal episode.']);
            }
            $student = User::query()->where('role', 'student')->findOrFail($withdrawal->student_id);
            $lastEnrollment = DB::table('class_enrollments as e')->leftJoin('academic_classes as c', 'c.id', '=', 'e.class_id')
                ->where('e.student_id', $student->id)->whereIn('e.status', ['withdrawn', 'transferred', 'promoted'])
                ->orderByDesc('e.ended_at')->orderByDesc('e.id')->select('c.display_name', 'c.name', 'e.academic_year')->first();
            $lastClass = $data['last_class_text'] ?? ($lastEnrollment->display_name ?? $lastEnrollment->name ?? '');
            $number = 'TC-' . now()->format('Y') . '-' . strtoupper(Str::random(8));
            return DB::table('student_exit_certificates')->insertGetId([
                'withdrawal_record_id' => $withdrawalId,
                'student_id' => $student->id,
                'certificate_number' => $number,
                'date_of_issue' => $data['date_of_issue'],
                'last_class' => $lastClass,
                'last_academic_year' => $lastEnrollment->academic_year ?? null,
                'conduct' => $data['conduct'] ?? null,
                'next_institution' => $data['next_institution'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'issued_by' => $actor->id,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });

        return redirect()->route('school.student-exits.certificate', ['id' => $certificateId])->with('success', 'Transfer certificate issued.');
    }

    public function certificate(Request $request, int $id): View
    {
        $this->admin($request);
        $record = DB::table('student_exit_certificates as c')
            ->join('users as u', 'u.id', '=', 'c.student_id')
            ->join('student_withdrawal_records as w', 'w.id', '=', 'c.withdrawal_record_id')
            ->leftJoin('users as a', 'a.id', '=', 'c.issued_by')
            ->where('c.id', $id)
            ->select('c.*', 'u.name as student_name', 'u.admission_number', 'u.date_of_birth', 'w.effective_date', 'w.reason as withdrawal_reason', 'a.name as issuer_name')
            ->first();
        abort_unless($record, 404);
        return view('school.student-exits.certificate', compact('record'));
    }
}
