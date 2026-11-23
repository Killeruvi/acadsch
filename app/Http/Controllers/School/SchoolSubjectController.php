<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AcademicSubject;
use App\Models\SchoolStream;
use App\Models\SubjectMaster;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SchoolSubjectController extends Controller
{
    public function index(Request $request): View|\Illuminate\Http\JsonResponse
    {
        abort_unless(Auth::user() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()), 403);

        $status = Auth::user()->isAdmin() ? $request->input('status', 'active') : 'active';
        $query = SubjectMaster::with('defaultTeacher');
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $selectedStream = $request->input('stream', 'all');
        if ($selectedStream !== 'all' && $selectedStream !== null) {
            $query->where(function ($q) use ($selectedStream) {
                $q->where('stream', $selectedStream)->orWhere('stream', 'All');
            });
        }
        if ($request->filled('category')) {
            $query->where('category', $request->string('category')->toString());
        }
        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"));
        }

        $subjects = $query->orderBy('category')->orderBy('name')->paginate(15)->withQueryString();
        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'subjects' => $subjects]);
        }

        // Build assignment counts in one pass instead of loading all rows once per master subject.
        $masterCounts = AcademicSubject::query()->whereNotNull('subject_master_id')
            ->selectRaw('subject_master_id, COUNT(*) AS total')->groupBy('subject_master_id')->pluck('total', 'subject_master_id')->all();
        $codeCounts = [];
        $nameCounts = [];
        foreach (AcademicSubject::query()->select('code', 'name')->whereNull('subject_master_id')->cursor() as $assignment) {
            $code = strtoupper(trim((string) $assignment->code));
            $baseCode = preg_replace('/-[0-9]+[A-Za-z]*$/', '', $code);
            $name = strtoupper(trim((string) $assignment->name));
            $codeCounts[$code] = ($codeCounts[$code] ?? 0) + 1;
            if ($baseCode !== $code) {
                $codeCounts[$baseCode] = ($codeCounts[$baseCode] ?? 0) + 1;
            }
            $nameCounts[$name] = ($nameCounts[$name] ?? 0) + 1;
        }

        $assignedClassesMap = [];
        foreach ($subjects as $master) {
            $code = strtoupper(trim($master->code));
            $name = strtoupper(trim($master->name));
            $assignedClassesMap[$master->id] = max($masterCounts[$master->id] ?? 0, $codeCounts[$code] ?? 0, $nameCounts[$name] ?? 0);
        }

        $teachers = \App\Models\User::where('role', 'teacher')->orderBy('name')->get();
        $streams = SchoolStream::active()->orderBy('name')->pluck('name')->all();
        $streams[] = 'All';
        $streams = array_values(array_unique($streams));
        $categories = SubjectMaster::query()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category')->all();
        $stats = [
            'total_subjects' => SubjectMaster::where('is_active', true)->count(),
            'total_streams' => SchoolStream::where('is_active', true)->count(),
            'total_categories' => count($categories),
            'active_assignments' => AcademicSubject::count(),
        ];

        return view('school.subjects.index', compact('subjects', 'assignedClassesMap', 'streams', 'categories', 'selectedStream', 'stats', 'status', 'teachers'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
        $request->merge(['name' => trim((string) $request->input('name')), 'code' => strtoupper(trim((string) $request->input('code')))]);
        $streamRule = Rule::in(array_merge(['All'], SchoolStream::active()->pluck('name')->all()));
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9][A-Z0-9_-]*$/', Rule::unique('subject_masters', 'code')->where(fn ($q) => $q->where('stream', $request->input('stream')))],
            'stream' => ['required', 'string', 'max:50', $streamRule],
            'category' => 'required|string|max:50',
            'default_periods' => 'required|integer|min:1|max:40',
            'min_grade' => ['required', 'integer', Rule::in(config('school.grade_levels', []))],
            'max_grade' => ['required', 'integer', Rule::in(config('school.grade_levels', [])), 'gte:min_grade'],
            'default_teacher_id' => 'nullable|integer|exists:users,id',
            'description' => 'nullable|string|max:1000',
        ]);
        if (!empty($data['default_teacher_id']) && !\App\Models\User::findOrFail($data['default_teacher_id'])->isTeacher()) {
            return back()->withInput()->withErrors(['default_teacher_id' => 'Select a teacher account.']);
        }
        $data['is_active'] = true;
        [$subject, $inheritance] = DB::transaction(function () use ($data, $request) {
            $subject = SubjectMaster::create($data);
            app(\App\Services\SchoolAcademicAuditService::class)->log(Auth::user(), 'created_subject_master', 'Created subject master '.$subject->name.' ('.$subject->code.').', null, $subject->only(['id', 'name', 'code', 'stream', 'category', 'default_periods', 'min_grade', 'max_grade', 'default_teacher_id', 'is_active']), $request);
            $inheritance = app(\App\Services\SubjectCurriculumInheritanceService::class)->inheritMasterAcrossClasses($subject, \App\Models\AcademySetting::get('academic_session'), Auth::user(), $request);
            return [$subject, $inheritance];
        }, 3);
        $message = 'Curriculum subject created. '.($inheritance['created'] + $inheritance['linked']).' class mappings created or linked for the active academic session.';
        if ($inheritance['skipped']) $message .= ' Review: '.implode(' ', array_unique($inheritance['skipped']));
        return back()->with('success', $message);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
        $subject = SubjectMaster::findOrFail($id);
        $request->merge(['name' => trim((string) $request->input('name')), 'code' => strtoupper(trim((string) $request->input('code')))]);
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9][A-Z0-9_-]*$/', Rule::unique('subject_masters', 'code')->ignore($subject->id)->where(fn ($q) => $q->where('stream', $request->input('stream')))],
            'stream' => ['required', 'string', 'max:50', Rule::in(array_merge(['All'], SchoolStream::active()->pluck('name')->all()))],
            'category' => 'required|string|max:50',
            'default_periods' => 'required|integer|min:1|max:40',
            'min_grade' => ['required', 'integer', Rule::in(config('school.grade_levels', []))],
            'max_grade' => ['required', 'integer', Rule::in(config('school.grade_levels', [])), 'gte:min_grade'],
            'default_teacher_id' => 'nullable|integer|exists:users,id',
            'description' => 'nullable|string|max:1000',
        ]);

        if (!empty($data['default_teacher_id']) && !\App\Models\User::findOrFail($data['default_teacher_id'])->isTeacher()) {
            return back()->withInput()->withErrors(['default_teacher_id' => 'Select a teacher account.']);
        }
        $hasClassMappings = AcademicSubject::where('subject_master_id', $subject->id)
            ->orWhereRaw('UPPER(code) = ?', [strtoupper($subject->code)])
            ->orWhereRaw('UPPER(name) = ?', [strtoupper($subject->name)])->exists();
        if ((int) ($subject->default_teacher_id ?? 0) !== (int) ($data['default_teacher_id'] ?? 0) && $hasClassMappings) {
            return back()->withInput()->withErrors(['default_teacher_id' => 'This subject is already mapped to classes. Change its global teacher through Teacher Assignment with Apply Globally so existing timetable conflicts are checked.']);
        }
        $identityChanged = strcasecmp($subject->name, $data['name']) !== 0 || strcasecmp($subject->code, $data['code']) !== 0 || strcasecmp($subject->stream, $data['stream']) !== 0;
        if ($identityChanged) {
            $mapped = AcademicSubject::where('subject_master_id', $subject->id)
                ->orWhereRaw('UPPER(code) = ?', [strtoupper($subject->code)])
                ->orWhereRaw('UPPER(name) = ?', [strtoupper($subject->name)])->exists();
            if ($mapped) {
                return back()->withInput()->withErrors(['name' => 'This master subject is already mapped to class curriculum. Its name, code and stream are locked to preserve existing class and examination references. You can still update category, periods and description.']);
            }
        }
        $oldValues = $subject->only(['name', 'code', 'stream', 'category', 'default_periods', 'min_grade', 'max_grade', 'default_teacher_id', 'description', 'is_active']);
        $subject->update($data);
        app(\App\Services\SchoolAcademicAuditService::class)->log(Auth::user(), 'updated_subject_master', 'Updated subject master '.$subject->name.'.', $oldValues, $subject->only(['name', 'code', 'stream', 'category', 'default_periods', 'min_grade', 'max_grade', 'default_teacher_id', 'description', 'is_active']), $request);
        return back()->with('success', 'Curriculum subject updated.');
    }

    /** Soft-deactivate master subjects; never delete curriculum history. */
    public function destroy(int $id): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
        $subject = SubjectMaster::findOrFail($id);
        $subject->update(['is_active' => false]);
        app(\App\Services\SchoolAcademicAuditService::class)->log(Auth::user(), 'deactivated_subject_master', 'Deactivated subject master '.$subject->name.'.', ['is_active' => true], ['is_active' => false], request());
        return back()->with('success', 'Subject master deactivated. Existing class subject, exam and timetable records were preserved.');
    }

    public function activate(int $id): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
        $subject = SubjectMaster::findOrFail($id);
        if ($subject->stream !== 'All' && !SchoolStream::where('name', $subject->stream)->where('is_active', true)->exists()) {
            return back()->withErrors(['stream' => 'Reactivate the subject stream before reactivating this subject.']);
        }
        $subject->update(['is_active' => true]);
        app(\App\Services\SchoolAcademicAuditService::class)->log(Auth::user(), 'reactivated_subject_master', 'Reactivated subject master '.$subject->name.'.', ['is_active' => false], ['is_active' => true], request());
        return back()->with('success', 'Subject master reactivated.');
    }
}
