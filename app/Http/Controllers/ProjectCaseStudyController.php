<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\AcademicProject;
use App\Models\AcademicSubject;
use App\Models\ProjectSubmission;
use App\Models\UserBadge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProjectCaseStudyController extends Controller
{
    protected function authorizeFaculty(): void
    {
        $user = Auth::user();
        if (!$user || (!$user->isTeacher() && !$user->isAdmin())) {
            abort(403, 'Unauthorized. Faculty credentials required.');
        }
    }
    public function index(Request $request): View
    {
        $user = Auth::user();
        $allClasses = AcademicClass::orderBy('grade_level')->get();

        $selectedClassId = $request->query('class_id');
        $typeFilter = $request->query('type', 'all'); // 'all', 'project', 'case_study'
        $modeFilter = $request->query('mode', 'all'); // 'all', 'individual', 'grouped'

        $query = AcademicProject::with(['teacher', 'academicClass', 'subject', 'submissions'])
            ->withCount('submissions');

        if ($selectedClassId) {
            $query->where('class_id', $selectedClassId);
        }

        if (in_array($typeFilter, ['project', 'case_study'], true)) {
            $query->where('type', $typeFilter);
        }

        if (in_array($modeFilter, ['individual', 'grouped'], true)) {
            $query->where('group_mode', $modeFilter);
        }

        $projects = $query->latest('due_date')->paginate(8)->withQueryString();
        $allSubjects = AcademicSubject::orderBy('name')->get();

        $mySubmissions = ProjectSubmission::with('project')
            ->where('student_id', $user->id)
            ->latest('submitted_at')
            ->take(5)
            ->get();

        return view('projects.index', compact('projects', 'allClasses', 'allSubjects', 'selectedClassId', 'typeFilter', 'modeFilter', 'mySubmissions'));
    }

    public function show(AcademicProject $project): View
    {
        $user = Auth::user();
        $project->load(['teacher', 'academicClass', 'subject', 'submissions.student']);

        $mySubmission = ProjectSubmission::where('project_id', $project->id)
            ->where('student_id', $user->id)
            ->latest('submitted_at')
            ->first();

        $submissions = $project->submissions()
            ->with('student')
            ->latest('submitted_at')
            ->paginate(10, ['*'], 'submissions_page')
            ->withQueryString();

        return view('projects.show', compact('project', 'mySubmission', 'submissions'));
    }

    public function submit(Request $request, AcademicProject $project): RedirectResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isStudent()) {
            abort(403, 'Only students can submit project deliverables.');
        }

        if ($user->current_class_id && (int)$project->class_id !== (int)$user->current_class_id) {
            abort(403, 'Unauthorized. You are not enrolled in the class assigned to this capstone project.');
        }

        $validated = $request->validate([
            'team_name' => $project->isGrouped() ? 'required|string|max:100' : 'nullable|string',
            'team_members' => 'nullable|string',
            'solution_summary' => 'required|string|min:20',
            'deliverable_url' => 'nullable|url|max:255',
        ]);

        $teamMembersList = [];
        if (!empty($validated['team_members'])) {
            $teamMembersList = array_map('trim', explode(',', $validated['team_members']));
        }

        ProjectSubmission::updateOrCreate([
            'project_id' => $project->id,
            'student_id' => $user->id,
        ], [
            'team_name' => $validated['team_name'] ?? null,
            'team_members' => $teamMembersList,
            'solution_summary' => $validated['solution_summary'],
            'deliverable_url' => $validated['deliverable_url'] ?? null,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $user->recordDailyActivity('assignment_submit', 75);

        return back()->with('success', 'Project deliverables submitted successfully for faculty review!');
    }

    public function evaluate(Request $request, ProjectSubmission $submission): RedirectResponse
    {
        $this->authorizeFaculty();
        $validated = $request->validate([
            'score' => 'required|integer|min:0|max:' . $submission->project->max_marks,
            'teacher_feedback' => 'nullable|string',
            'status' => 'required|in:evaluated,resubmission_required',
        ]);

        $submission->update([
            'score' => $validated['score'],
            'teacher_feedback' => $validated['teacher_feedback'] ?? null,
            'status' => $validated['status'],
        ]);

        // Award "Project Architect" badge if student scored high (> 80%)
        if ($submission->score >= ($submission->project->max_marks * 0.8)) {
            UserBadge::firstOrCreate([
                'user_id' => $submission->student_id,
                'badge_key' => 'project_architect',
            ], [
                'title' => 'Project Architect',
                'icon' => '🚀',
                'category' => 'academics',
                'description' => "Demonstrated exemplary real-world research & capstone problem solving!",
                'awarded_at' => now(),
            ]);
        }

        return back()->with('success', 'Project submission evaluated and score awarded!');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeFaculty();
        $validated = $request->validate([
            'class_id' => 'required|exists:academic_classes,id',
            'subject_id' => 'nullable|exists:academic_subjects,id',
            'title' => 'required|string|max:255',
            'type' => 'required|in:project,case_study',
            'group_mode' => 'required|in:individual,grouped',
            'max_team_size' => 'required|integer|min:1|max:10',
            'description' => 'required|string',
            'problem_statement' => 'nullable|string',
            'evaluation_rubric' => 'nullable|string',
            'due_date' => 'required|date|after_or_equal:today',
            'max_marks' => 'required|integer|min:10|max:500',
        ]);

        $validated['teacher_id'] = Auth::id();

        $project = AcademicProject::create($validated);

        return redirect()->route('projects.show', $project->id)
            ->with('success', "{$project->title} created successfully!");
    }

    public function edit(AcademicProject $project): View
    {
        $this->authorizeFaculty();
        $allClasses = AcademicClass::orderBy('grade_level')->get();
        $allSubjects = AcademicSubject::orderBy('name')->get();

        return view('projects.edit', compact('project', 'allClasses', 'allSubjects'));
    }

    public function update(Request $request, AcademicProject $project): RedirectResponse
    {
        $this->authorizeFaculty();
        $validated = $request->validate([
            'class_id' => 'required|exists:academic_classes,id',
            'subject_id' => 'nullable|exists:academic_subjects,id',
            'title' => 'required|string|max:255',
            'type' => 'required|in:project,case_study',
            'group_mode' => 'required|in:individual,grouped',
            'max_team_size' => 'required|integer|min:1|max:10',
            'description' => 'required|string',
            'problem_statement' => 'nullable|string',
            'evaluation_rubric' => 'nullable|string',
            'due_date' => 'required|date',
            'max_marks' => 'required|integer|min:10|max:500',
        ]);

        $project->update($validated);

        return redirect()->route('projects.show', $project->id)
            ->with('success', "{$project->title} updated successfully!");
    }

    public function destroy(AcademicProject $project): RedirectResponse
    {
        $this->authorizeFaculty();
        $title = $project->title;
        $project->submissions()->delete();
        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', "Project '{$title}' and its submissions were removed.");
    }
}
