<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AcademicClass;
use App\Models\SchoolSection;
use App\Models\SchoolStream;
use App\Services\SchoolAcademicAuditService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SchoolSectionController extends Controller
{
    /**
     * Get list of reusable sections, optionally filtered by stream.
     * Renders a compact UI view for browser requests and JSON for API / AJAX calls.
     */
    public function index(Request $request): JsonResponse|View
    {
        $status = $request->input('status', 'active');
        $query = SchoolSection::query();
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive' && Auth::user()?->isAdmin()) {
            $query->where('is_active', false);
        } elseif ($status === 'all' && Auth::user()?->isAdmin()) {
            // Administrators may inspect both active and inactive master records.
        } else {
            $query->where('is_active', true);
        }

        $selectedStream = $request->input('stream');
        if ($request->filled('stream') && $selectedStream !== 'all') {
            $query->where('stream', $selectedStream);
        }

        $search = $request->input('search');
        if ($request->filled('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('room_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $sections = $query->orderBy('stream')->orderBy('name')->paginate(12)->withQueryString();

        if ($request->expectsJson() && ! $request->acceptsHtml()) {
            return response()->json([
                'success' => true,
                'sections' => $sections,
            ]);
        }

        // Calculate assigned classes per section to avoid N+1 queries
        $classesQuery = AcademicClass::query()->select('id', 'name', 'grade_level', 'stream', 'section', 'section_id');
        $viewer = Auth::user();
        if ($viewer && $viewer->isTeacher()) {
            $teacherId = $viewer->id;
            $classesQuery->where(function ($query) use ($teacherId) {
                $query->where('class_teacher_id', $teacherId)
                    ->orWhereHas('subjects', fn ($subjects) => $subjects->where('teacher_id', $teacherId))
                    ->orWhereHas('timetables', fn ($timetables) => $timetables->where('teacher_id', $teacherId));
            });
        }
        $classes = $classesQuery->get();

        $assignedMap = [];
        foreach ($sections as $section) {
            $assigned = $classes->filter(function ($c) use ($section) {
                return $c->section_id === $section->id ||
                    (strcasecmp((string) $c->stream, (string) $section->stream) === 0 &&
                     strcasecmp($c->section ?? '', $section->name ?? '') === 0);
            });
            $assignedMap[$section->id] = $assigned;
        }

        $allSections = SchoolSection::where('is_active', true)->get();
        $streams = SchoolStream::query()->active()->orderBy('name')->pluck('name')->all();
        $stats = [
            'total_sections' => $allSections->count(),
            'total_capacity' => (int) $allSections->sum('capacity'),
            'total_streams' => count($streams),
            'assigned_classes_count' => $classes->count(),
        ];

        return view('school.sections.index', compact(
            'sections',
            'assignedMap',
            'streams',
            'selectedStream',
            'search',
            'status',
            'stats'
        ));
    }

    /**
     * Create a new reusable section under a stream.
     * Prevents duplicate sections under the same stream.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Only administrators can create reusable sections.');
        }

        $request->merge([
            'name' => strtoupper(trim((string) $request->input('name'))),
            'stream' => trim((string) $request->input('stream')),
        ]);

        $validated = $request->validate([
            'stream' => ['required', 'string', 'max:50', Rule::exists('school_streams', 'name')->where('is_active', true)],
            'name' => ['required', 'string', 'size:1', 'regex:/^[A-Z]$/'],
            'room_number' => 'nullable|string|max:50',
            'capacity' => 'required|integer|min:'.config('school.capacity.minimum').'|max:'.config('school.capacity.maximum'),
            'description' => 'nullable|string|max:255',
        ], [
            'name.size' => 'Section names must be a single letter from A to Z.',
            'name.regex' => 'Section names must be a single letter from A to Z.',
        ]);

        try {
            $section = DB::transaction(function () use ($validated) {
                $duplicate = SchoolSection::query()
                    ->where('stream', $validated['stream'])
                    ->whereRaw('UPPER(name) = ?', [$validated['name']])
                    ->lockForUpdate()
                    ->exists();

                if ($duplicate) {
                    throw ValidationException::withMessages([
                        'name' => "Section {$validated['name']} already exists in {$validated['stream']}.",
                    ]);
                }

                return SchoolSection::create([
                    ...$validated,
                    'is_active' => true,
                ]);
            }, 3);
        } catch (ValidationException $exception) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                    'errors' => $exception->errors(),
                ], 422);
            }
            throw $exception;
        } catch (QueryException $exception) {
            report($exception);

            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => 'The section could not be created because it conflicts with an existing record.'], 409)
                : back()->withInput()->withErrors(['name' => 'The section conflicts with an existing record.']);
        }

        app(SchoolAcademicAuditService::class)->log(Auth::user(), 'created_section', 'Created section '.$section->name.' in '.$section->stream.'.', null, ['section_id' => $section->id, 'name' => $section->name, 'stream' => $section->stream, 'capacity' => $section->capacity], $request);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Section {$section->name} ({$section->stream}) created successfully.",
                'section' => $section,
            ], 201);
        }

        return redirect()->route('school.sections.index')->with('success', "Section {$section->name} ({$section->stream}) created successfully.");
    }

    /** Update reusable section metadata. Identity changes are blocked while classes reference it. */
    public function update(Request $request, int $id): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
        $section = SchoolSection::findOrFail($id);
        $data = $request->validate([
            'room_number' => 'nullable|string|max:50',
            'capacity' => 'required|integer|min:'.config('school.capacity.minimum').'|max:'.config('school.capacity.maximum'),
            'description' => 'nullable|string|max:255',
        ]);

        $maxActiveEnrollment = AcademicClass::query()
            ->where(function ($query) use ($section) {
                $query->where('section_id', $section->id)
                    ->orWhere(function ($legacy) use ($section) {
                        $legacy->where('stream', $section->stream)->where('section', $section->name);
                    });
            })
            ->withCount(['enrollments as active_enrollments_count' => fn ($q) => $q->where('status', 'active')])
            ->get()->max('active_enrollments_count') ?? 0;

        $maxLinkedClassCapacity = AcademicClass::query()
            ->where(function ($query) use ($section) {
                $query->where('section_id', $section->id)
                    ->orWhere(function ($legacy) use ($section) {
                        $legacy->where('stream', $section->stream)->where('section', $section->name);
                    });
            })->max('capacity') ?? 0;
        $minimumRequiredCapacity = max((int) $maxActiveEnrollment, (int) $maxLinkedClassCapacity);
        if ((int) $data['capacity'] < $minimumRequiredCapacity) {
            return back()->withInput()->withErrors(['capacity' => "Capacity cannot be lower than the largest linked class capacity or active roster ({$minimumRequiredCapacity})."]);
        }

        $oldValues = $section->only(['room_number', 'capacity', 'description']);
        $section->update($data);
        app(SchoolAcademicAuditService::class)->log(Auth::user(), 'updated_section', 'Updated section '.$section->name.' in '.$section->stream.'.', $oldValues, $section->only(['room_number', 'capacity', 'description']), $request);

        return back()->with('success', 'Section details updated.');
    }

    /** Deactivate/reactivate without deleting historical class references. */
    public function toggle(int $id): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
        $section = SchoolSection::findOrFail($id);
        $streamIsActive = SchoolStream::where('name', $section->stream)->where('is_active', true)->exists();
        if (! $section->is_active && ! $streamIsActive) {
            return back()->withErrors(['section' => 'Reactivate the parent stream before reactivating this section.']);
        }
        $oldActive = $section->is_active;
        $section->is_active = ! $section->is_active;
        $section->save();
        app(SchoolAcademicAuditService::class)->log(Auth::user(), 'toggled_section', 'Changed activation state for section '.$section->name.' in '.$section->stream.'.', ['is_active' => $oldActive], ['is_active' => $section->is_active], request());

        return back()->with('success', $section->is_active ? 'Section reactivated for new classes.' : 'Section deactivated. Existing classes remain unchanged.');
    }

    /** Permanent deletion is intentionally replaced by deactivation. */
    public function destroy(int $id): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
        $section = SchoolSection::findOrFail($id);
        $oldActive = $section->is_active;
        $section->update(['is_active' => false]);
        app(SchoolAcademicAuditService::class)->log(Auth::user(), 'deactivated_section', 'Deactivated section '.$section->name.' in '.$section->stream.'.', ['is_active' => $oldActive], ['is_active' => false], request());

        return redirect()->route('school.sections.index')->with('success', 'Section deactivated. Historical class links were preserved.');
    }
}
