<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AcademicClass;
use App\Models\StudyMaterial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudyMaterialController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = StudyMaterial::with(['academicClass', 'author:id,name'])->latest('upload_date');

        if ($user->isStudent()) {
            $classId = $user->current_class_id ?? $user->batch?->class_id;
            $query->whereIn('available_for', ['all', 'students'])
                ->where(function ($q) use ($classId) {
                    $q->whereNull('class_id');
                    if ($classId) $q->orWhere('class_id', $classId);
                });
        } elseif ($user->isParent()) {
            $childClassIds = \App\Models\User::where('parent_id', $user->id)
                ->whereNotNull('current_class_id')->pluck('current_class_id')->unique();
            $query->whereIn('available_for', ['all', 'students'])
                ->where(function ($q) use ($childClassIds) {
                    $q->whereNull('class_id');
                    if ($childClassIds->isNotEmpty()) $q->orWhereIn('class_id', $childClassIds);
                });
        } elseif ($user->isTeacher()) {
            $query->whereIn('available_for', ['all', 'teachers']);
        }

        $allowedTypes = ['assignment', 'syllabus', 'study_material', 'other_download'];
        $type = $request->query('type');
        if ($type !== null && in_array($type, $allowedTypes, true)) $query->where('content_type', $type);

        $materials = $query->paginate(12)->withQueryString();
        $classes = $user->isAdmin() || $user->isTeacher()
            ? AcademicClass::orderBy('grade_level')->get()
            : collect();

        return view('school.study-materials.index', compact('materials', 'classes', 'type'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isTeacher()), 403);

        $validated = $request->validate([
            'content_title' => 'required|string|max:200',
            'content_type' => 'required|string|in:assignment,syllabus,study_material,other_download',
            'class_id' => 'nullable|exists:academic_classes,id',
            'available_for' => 'required|string|in:all,students,teachers',
            'material_file' => 'required|file|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,csv,txt,jpg,jpeg,png,webp|max:20480',
            'description' => 'nullable|string|max:2000',
        ]);

        if ($user->isTeacher()) {
            // Teachers may publish only to a specific class they are assigned to.
            abort_unless(!empty($validated['class_id']), 403, 'Select a class assigned to you before publishing.');
            $validated['available_for'] = 'students';
            $classId = (int) $validated['class_id'];
            $assigned = \App\Models\AcademicSubject::where('teacher_id', $user->id)
                ->whereHas('academicClass', fn ($q) => $q->whereKey($classId))->exists()
                || \App\Models\AcademicClass::whereKey($classId)->where('class_teacher_id', $user->id)->exists();
            abort_unless($assigned, 403, 'You can publish materials only for classes assigned to you.');
        }

        $file = $validated['material_file'];
        $path = $file->store('study-materials/' . now()->format('Y/m'), 'local');
        abort_unless(is_string($path) && $path !== '', 500, 'The uploaded file could not be stored.');

        StudyMaterial::create([
            'content_title' => $validated['content_title'],
            'content_type' => $validated['content_type'],
            'class_id' => $validated['class_id'] ?? null,
            'available_for' => $validated['available_for'],
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'upload_date' => now()->toDateString(),
            'description' => $validated['description'] ?? null,
            'created_by' => $user->id,
        ]);

        return back()->with('success', 'Study material uploaded and published successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isTeacher()), 403);
        $material = StudyMaterial::findOrFail($id);
        if ($user->isTeacher()) abort_unless((int) $material->created_by === (int) $user->id, 403);

        if ($material->file_path) Storage::disk('local')->delete($material->file_path);
        $material->delete();
        return back()->with('success', 'Study material deleted successfully.');
    }

    public function download(int $id): StreamedResponse
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isTeacher() || $user->isStudent() || $user->isParent()), 403);
        $material = StudyMaterial::findOrFail($id);

        if ($user->isStudent()) {
            $classId = $user->current_class_id ?? $user->batch?->class_id;
            abort_unless(in_array($material->available_for, ['all', 'students'], true)
                && ($material->class_id === null || (int) $material->class_id === (int) $classId), 403);
        } elseif ($user->isParent()) {
            $children = \App\Models\User::childrenOf($user);
            $childClassIds = (clone $children)->pluck('current_class_id')->filter()->map(fn ($v) => (int) $v);
            $hasEnrolledChild = (clone $children)->whereHas('classEnrollments', function ($q) use ($material) {
                $q->whereIn('status', ['active', 'enrolled'])->where('class_id', $material->class_id);
            })->exists();
            abort_unless(in_array($material->available_for, ['all', 'students'], true)
                && ($material->class_id === null || $childClassIds->contains((int) $material->class_id) || $hasEnrolledChild), 403);
        } elseif ($user->isTeacher()) {
            abort_unless(in_array($material->available_for, ['all', 'teachers'], true), 403);
        } elseif (!$user->isAdmin()) {
            abort(403);
        }

        abort_unless($material->file_path && Storage::disk('local')->exists($material->file_path), 404, 'Material file not found.');
        $safeName = preg_replace('/[^A-Za-z0-9._ -]/', '_', basename($material->file_name ?: 'study-material'));
        return Storage::disk('local')->download($material->file_path, $safeName);
    }
}
