<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AcademicClass;
use App\Models\ClassEnrollment;
use App\Models\SchoolExamMark;
use App\Models\SchoolExamTerm;
use App\Models\SchoolReportCard;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AcademicProgressionController extends Controller
{
    private function admin(Request $request): User
    {
        $user = $request->user();
        abort_unless($user && $user->isAdmin(), 403);

        return $user;
    }

    public function policy(Request $request): View
    {
        $this->admin($request);
        $classes = AcademicClass::query()->orderBy('academic_year')->orderBy('grade_level')->get();
        $selected = $request->integer('class_id') ?: null;
        $class = $selected ? AcademicClass::findOrFail($selected) : null;
        $policy = $class ? DB::table('school_promotion_policies')->where('academic_year', $class->academic_year)->where('source_class_id', $class->id)->first() : null;

        return view('school.progression.policy', compact('classes', 'class', 'policy'));
    }

    public function savePolicy(Request $request)
    {
        $this->admin($request);
        $data = $request->validate([
            'class_id' => ['required', 'integer', 'exists:academic_classes,id'],
            'minimum_overall_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'minimum_subject_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'require_all_subject_marks' => ['nullable', 'boolean'],
            'require_published_result' => ['nullable', 'boolean'],
        ]);
        $class = AcademicClass::findOrFail($data['class_id']);
        DB::table('school_promotion_policies')->updateOrInsert(
            ['academic_year' => $class->academic_year, 'source_class_id' => $class->id],
            ['minimum_overall_percentage' => $data['minimum_overall_percentage'], 'minimum_subject_percentage' => $data['minimum_subject_percentage'],
                'require_all_subject_marks' => $request->boolean('require_all_subject_marks'), 'require_published_result' => $request->boolean('require_published_result'), 'updated_at' => now(), 'created_at' => now()]
        );

        return redirect()->route('school.progression.policy', ['class_id' => $class->id])->with('success', 'Promotion policy saved for '.$class->academic_year.'.');
    }

    public function assess(AcademicClass $class, int $studentId): array
    {
        $policy = DB::table('school_promotion_policies')->where('academic_year', $class->academic_year)->where('source_class_id', $class->id)->first();
        if (! $policy) {
            return ['eligible' => true, 'reason' => 'No policy configured for source class', 'percentage' => null];
        }
        $overallMin = (float) ($policy->minimum_overall_percentage ?? 35);
        $subjectMin = (float) ($policy->minimum_subject_percentage ?? 35);
        $requireAll = (bool) ($policy->require_all_subject_marks ?? true);
        $requirePublished = (bool) ($policy->require_published_result ?? true);
        $term = SchoolExamTerm::query()->where('academic_year', $class->academic_year)->where('status', 'completed')->orderByDesc('end_date')->first();
        if (! $term) {
            return ['eligible' => false, 'reason' => 'No completed examination term in this session', 'percentage' => null];
        }
        $report = SchoolReportCard::query()->where('exam_term_id', $term->id)->where('class_id', $class->id)->where('student_id', $studentId)->first();
        if (! $report || ($requirePublished && ! $report->is_published)) {
            return ['eligible' => false, 'reason' => 'Published final result is missing', 'percentage' => null];
        }
        $subjects = DB::table('academic_subjects')->where('class_id', $class->id)->pluck('id');
        $marks = SchoolExamMark::query()->where('exam_term_id', $term->id)->where('class_id', $class->id)->where('student_id', $studentId)->get();
        if ($subjects->isEmpty()) {
            return ['eligible' => false, 'reason' => 'No subjects configured for source class', 'percentage' => (float) $report->percentage];
        }
        if ($requireAll && $marks->pluck('subject_id')->unique()->count() < $subjects->count()) {
            return ['eligible' => false, 'reason' => 'One or more subject marks are missing', 'percentage' => (float) $report->percentage];
        }
        $failed = $marks->first(fn ($m) => (float) $m->marks_obtained < (float) $m->pass_marks || ((float) $m->max_marks > 0 && ((float) $m->marks_obtained / (float) $m->max_marks * 100) < $subjectMin));
        if ($failed) {
            return ['eligible' => false, 'reason' => 'Failed subject: '.(DB::table('academic_subjects')->where('id', $failed->subject_id)->value('name') ?? 'Unknown'), 'percentage' => (float) $report->percentage];
        }
        if ((float) $report->percentage < $overallMin || strtoupper((string) $report->result_status) !== 'PASS') {
            return ['eligible' => false, 'reason' => 'Overall result does not meet promotion policy', 'percentage' => (float) $report->percentage];
        }

        return ['eligible' => true, 'reason' => 'Meets configured examination policy', 'percentage' => (float) $report->percentage];
    }

    public function preview(Request $request): View
    {
        $this->admin($request);
        $classes = AcademicClass::query()->orderBy('grade_level')->orderBy('section')->get();
        $source = $request->integer('source_class_id') ? AcademicClass::findOrFail($request->integer('source_class_id')) : null;
        $target = $request->integer('target_class_id') ? AcademicClass::findOrFail($request->integer('target_class_id')) : null;
        $rows = collect();
        if ($source) {
            $rows = ClassEnrollment::query()->with('student')->where('class_id', $source->id)->where('academic_year', $source->academic_year)->where('status', 'active')->get()->filter(fn ($e) => $e->student?->role === 'student')->map(function ($e) use ($source) {
                $result = $this->assess($source, (int) $e->student_id);

                return ['student' => $e->student, 'enrollment' => $e, 'eligible' => $result['eligible'], 'reason' => $result['reason'], 'percentage' => $result['percentage']];
            });
        }

        return view('school.progression.preview', compact('classes', 'source', 'target', 'rows'));
    }

    public function bulkPromote(Request $request)
    {
        $actor = $this->admin($request);
        $data = $request->validate(['source_class_id' => ['required', 'integer', 'exists:academic_classes,id'], 'target_class_id' => ['required', 'integer', 'different:source_class_id', 'exists:academic_classes,id'], 'student_ids' => ['required', 'array', 'min:1', 'max:200'], 'student_ids.*' => ['required', 'integer', 'distinct', 'exists:users,id']]);
        $source = AcademicClass::findOrFail($data['source_class_id']);
        $target = AcademicClass::findOrFail($data['target_class_id']);
        if ($target->academic_year === $source->academic_year || (int) $target->grade_level !== (int) $source->grade_level + 1) {
            throw ValidationException::withMessages(['target_class_id' => 'Choose the next grade in a different academic session.']);
        }
        $ids = collect($data['student_ids'])->map(fn ($id) => (int) $id)->unique();
        foreach ($ids as $id) {
            $check = $this->assess($source, $id);
            if (! $check['eligible']) {
                throw ValidationException::withMessages(['student_ids' => 'Bulk promotion stopped: student '.$id.' is not eligible — '.$check['reason'].'. Refresh the preview.']);
            }
        }
        DB::transaction(function () use ($ids, $source, $target, $actor) {
            $targetCount = ClassEnrollment::where('class_id', $target->id)->where('academic_year', $target->academic_year)->where('status', 'active')->count();
            if ($target->capacity && $targetCount + $ids->count() > $target->capacity) {
                throw ValidationException::withMessages(['target_class_id' => 'Target class capacity is insufficient.']);
            }
            foreach ($ids as $id) {
                $student = User::where('role', 'student')->lockForUpdate()->findOrFail($id);
                $old = ClassEnrollment::where('class_id', $source->id)->where('academic_year', $source->academic_year)->where('student_id', $id)->where('status', 'active')->lockForUpdate()->first();
                if (! $old || ! $student->is_approved || (int) $student->current_class_id !== (int) $source->id) {
                    throw ValidationException::withMessages(['student_ids' => 'A selected student is no longer actively enrolled.']);
                }
                if (ClassEnrollment::where('student_id', $id)->where('academic_year', $target->academic_year)->where('status', 'active')->exists()) {
                    throw ValidationException::withMessages(['student_ids' => 'A selected student already has an active enrollment in the target session.']);
                }
                $roll = (int) ClassEnrollment::where('class_id', $target->id)->where('academic_year', $target->academic_year)->max('roll_number') + 1;
                $old->update(['status' => 'promoted', 'ended_at' => now(), 'ended_by' => $actor->id, 'transition_reason' => 'Examination-policy promotion to '.$target->display_name]);
                ClassEnrollment::create(['class_id' => $target->id, 'student_id' => $id, 'roll_number' => $roll, 'admission_number' => $student->admission_number, 'academic_year' => $target->academic_year, 'status' => 'active']);
                $student->forceFill(['current_class_id' => $target->id, 'roll_number' => $roll, 'academic_tier' => $target->display_name])->save();
                DB::table('school_promotion_audits')->insert(['student_id' => $id, 'source_class_id' => $source->id, 'target_class_id' => $target->id, 'source_year' => $source->academic_year, 'target_year' => $target->academic_year, 'decision' => 'promoted', 'reason' => 'Met configured examination promotion policy', 'actor_id' => $actor->id, 'created_at' => now(), 'updated_at' => now()]);
            }
        });

        return redirect()->route('school.progression.preview', ['source_class_id' => $source->id, 'target_class_id' => $target->id])->with('success', $ids->count().' students promoted; audit records created.');
    }

    public function auditCsv(Request $request): StreamedResponse
    {
        $this->admin($request);
        $query = DB::table('school_promotion_audits')->leftJoin('users', 'users.id', '=', 'school_promotion_audits.student_id')
            ->leftJoin('academic_classes as src', 'src.id', '=', 'school_promotion_audits.source_class_id')
            ->leftJoin('academic_classes as dst', 'dst.id', '=', 'school_promotion_audits.target_class_id')
            ->select('school_promotion_audits.*', 'users.name as student_name', 'users.admission_number', 'src.name as source_class', 'dst.name as target_class')
            ->orderBy('school_promotion_audits.id');
        if ($request->filled('source_class_id')) {
            $query->where('school_promotion_audits.source_class_id', $request->integer('source_class_id'));
        }
        $rows = $query->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Audit ID', 'Student', 'Admission Number', 'Source Class', 'Source Session', 'Target Class', 'Target Session', 'Decision', 'Reason', 'Actor ID', 'Timestamp']);
            foreach ($rows as $r) {
                fputcsv($out, [$r->id, $r->student_name, $r->admission_number, $r->source_class, $r->source_year, $r->target_class, $r->target_year, $r->decision, $r->reason, $r->actor_id, $r->created_at]);
            }
            fclose($out);
        }, 'student-promotion-audit-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function graduate(Request $request)
    {
        $actor = $this->admin($request);
        $data = $request->validate(['student_id' => ['required', 'integer', 'exists:users,id'], 'class_id' => ['required', 'integer', 'exists:academic_classes,id'], 'date_of_leaving' => ['required', 'date'], 'reason_for_leaving' => ['nullable', 'string', 'max:255']]);
        $class = AcademicClass::findOrFail($data['class_id']);
        if ((int) $class->grade_level !== 12) {
            throw ValidationException::withMessages(['class_id' => 'Graduation and school leaving certificates are available only for Grade 12.']);
        }
        $enrolled = ClassEnrollment::where('class_id', $class->id)->where('academic_year', $class->academic_year)->where('student_id', $data['student_id'])->where('status', 'active')->exists();
        if (! $enrolled) {
            throw ValidationException::withMessages(['student_id' => 'Student is not actively enrolled in this Grade 12 session.']);
        }
        $result = $this->assess($class, (int) $data['student_id']);
        if (! $result['eligible']) {
            throw ValidationException::withMessages(['student_id' => 'Cannot issue a graduation certificate: '.$result['reason'].'.']);
        }
        $recordId = DB::transaction(function () use ($data, $class, $actor) {
            $existing = DB::table('school_graduation_records')->where('student_id', $data['student_id'])->where('academic_year', $class->academic_year)->first();
            if ($existing) {
                throw ValidationException::withMessages(['student_id' => 'A leaving certificate has already been issued for this student and session.']);
            }
            $number = 'LC-'.now()->format('Y').'-'.strtoupper(Str::random(8));

            return DB::table('school_graduation_records')->insertGetId([
                'student_id' => $data['student_id'], 'class_id' => $class->id, 'academic_year' => $class->academic_year, 'certificate_number' => $number,
                'date_of_leaving' => $data['date_of_leaving'], 'reason_for_leaving' => $data['reason_for_leaving'] ?? null, 'result_status' => 'PASS', 'issued_by' => $actor->id, 'created_at' => now(), 'updated_at' => now(),
            ]);
        });

        return redirect()->route('school.progression.certificate', ['id' => $recordId])->with('success', 'Grade 12 leaving certificate issued.');
    }

    public function certificate(Request $request, int $id): View
    {
        $this->admin($request);
        $record = DB::table('school_graduation_records')->join('users', 'users.id', '=', 'school_graduation_records.student_id')->join('academic_classes', 'academic_classes.id', '=', 'school_graduation_records.class_id')->where('school_graduation_records.id', $id)->select('school_graduation_records.*', 'users.name as student_name', 'users.admission_number', 'academic_classes.name as class_name', 'academic_classes.grade_level')->first();
        abort_unless($record, 404);

        return view('school.progression.certificate', compact('record'));
    }
}
