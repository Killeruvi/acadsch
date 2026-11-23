<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AcademicClass;
use App\Models\SchoolSection;
use App\Models\SchoolStream;
use App\Services\SchoolAcademicAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SchoolStreamController extends Controller
{
    public function index(): View
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        $streams = SchoolStream::query()
            ->withCount('sections')
            ->orderBy('name')
            ->paginate(30);

        $classCounts = AcademicClass::query()
            ->select('stream', DB::raw('COUNT(*) AS classes_count'))
            ->groupBy('stream')->pluck('classes_count', 'stream');

        return view('school.streams.index', compact('streams', 'classCounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        $request->merge(['name' => trim((string) $request->input('name'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', 'regex:/^[\pL\pN][\pL\pN &()\/-]*$/u', Rule::unique('school_streams', 'name')],
        ]);

        if (SchoolStream::where('name_key', mb_strtoupper($data['name']))->exists()) {
            return back()->withInput()->withErrors(['name' => 'A stream with this name already exists, ignoring letter case.']);
        }
        $stream = SchoolStream::create(['name' => $data['name'], 'is_active' => true]);
        app(SchoolAcademicAuditService::class)->log(Auth::user(), 'created_stream', 'Created academic stream '.$stream->name.'.', null, ['stream_id' => $stream->id, 'name' => $stream->name], $request);

        return back()->with('success', 'Academic stream created.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        $stream = SchoolStream::findOrFail($id);
        $request->merge(['name' => trim((string) $request->input('name'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', 'regex:/^[\pL\pN][\pL\pN &()\/-]*$/u', Rule::unique('school_streams', 'name')->ignore($stream->id)],
        ]);

        if (SchoolStream::where('name_key', mb_strtoupper($data['name']))->where('id', '!=', $stream->id)->exists()) {
            return back()->withInput()->withErrors(['name' => 'A stream with this name already exists, ignoring letter case.']);
        }

        if (strcasecmp($stream->name, $data['name']) !== 0) {
            $hasReferences = SchoolSection::where('stream', $stream->name)->exists()
                || AcademicClass::where('stream', $stream->name)->exists();
            if ($hasReferences) {
                return back()->withInput()->withErrors(['name' => 'This stream is already referenced by sections or classes. Create a new stream and migrate records through an approved academic-data migration instead of renaming it.']);
            }
        }

        $oldValues = ['name' => $stream->name, 'is_active' => $stream->is_active];
        $stream->update($data);
        app(SchoolAcademicAuditService::class)->log(Auth::user(), 'updated_stream', 'Updated academic stream '.$stream->name.'.', $oldValues, ['name' => $stream->name, 'is_active' => $stream->is_active], $request);

        return back()->with('success', 'Academic stream updated.');
    }

    public function toggle(int $id): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        $stream = SchoolStream::findOrFail($id);
        $oldActive = $stream->is_active;
        $stream->is_active = ! $stream->is_active;
        $stream->save();
        app(SchoolAcademicAuditService::class)->log(Auth::user(), 'toggled_stream', 'Changed activation state for stream '.$stream->name.'.', ['is_active' => $oldActive], ['is_active' => $stream->is_active], request());

        return back()->with('success', $stream->is_active ? 'Academic stream reactivated for new assignments.' : 'Academic stream deactivated. Existing classes and records are preserved.');
    }
}
