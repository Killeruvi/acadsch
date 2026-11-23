<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserCertificate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CertificateBadgeController extends Controller
{
    /** Classes for which this teacher has a current teaching responsibility. */
    private function assignedClassIds(User $teacher)
    {
        return AcademicClass::query()
            ->where(fn ($q) => $q->where('class_teacher_id', $teacher->id)
                ->orWhereHas('subjects', fn ($sq) => $sq->where('teacher_id', $teacher->id))
                ->orWhereHas('timetables', fn ($tq) => $tq->where('teacher_id', $teacher->id)))
            ->pluck('id');
    }

    private function teacherCanAccessStudent(User $teacher, User $student): bool
    {
        if (! $student->isStudent()) {
            return false;
        }
        $classIds = $this->assignedClassIds($teacher);

        return ($student->current_class_id && $classIds->contains((int) $student->current_class_id))
            || $student->classEnrollments()->where('status', 'active')->whereIn('class_id', $classIds)->exists()
            || $student->batch()->whereIn('class_id', $classIds)->exists();
    }

    public function index(Request $request): View
    {
        $actor = Auth::user();
        abort_unless($actor, 401);

        if ($actor->isStudent()) {
            $viewUser = $actor;
        } elseif ($actor->isParent()) {
            $children = User::query()->where('role', 'student')->where(function ($q) use ($actor) {
                $q->where('parent_id', $actor->id);
                if ($actor->email) {
                    $q->orWhere(fn ($legacy) => $legacy->whereNull('parent_id')->where('guardian_email', $actor->email));
                }
            })->orderBy('name')->get();
            $targetId = (int) $request->query('user_id', $children->first()?->id);
            $viewUser = $children->firstWhere('id', $targetId);
            abort_unless($viewUser, 403, 'You may only view credentials belonging to your linked children.');
        } elseif ($actor->isAdmin()) {
            $targetId = $request->query('user_id');
            $viewUser = User::query()->where('role', 'student')->when($targetId, fn ($q) => $q->whereKey($targetId))->orderBy('name')->firstOrFail();
        } elseif ($actor->isTeacher()) {
            $targetId = $request->query('user_id');
            if (! $targetId) {
                $classIds = $this->assignedClassIds($actor);
                $targetId = User::query()->where('role', 'student')->where(function ($q) use ($classIds) {
                    $q->whereIn('current_class_id', $classIds)
                        ->orWhereHas('classEnrollments', fn ($eq) => $eq->where('status', 'active')->whereIn('class_id', $classIds))
                        ->orWhereHas('batch', fn ($bq) => $bq->whereIn('class_id', $classIds));
                })->orderBy('name')->value('id');
            }
            abort_unless($targetId, 404, 'No students are available in your assigned classes.');
            $viewUser = User::query()->where('role', 'student')->findOrFail($targetId);
            abort_unless($this->teacherCanAccessStudent($actor, $viewUser), 403, 'You may only view students in your assigned classes.');
        } else {
            abort(403);
        }

        $certificates = UserCertificate::with('issuer')
            ->where('user_id', $viewUser->id)
            ->latest('issued_date')
            ->paginate(8, ['*'], 'certificates_page')
            ->withQueryString();
        $badges = UserBadge::where('user_id', $viewUser->id)->latest('awarded_at')->get();

        $allSystemBadges = [
            ['key' => 'streak_titan', 'title' => 'Streak Titan', 'icon' => '🔥', 'category' => 'streaks', 'desc' => 'Maintained a persistent 7+ day active learning streak.'],
            ['key' => 'speed_quizzer', 'title' => 'Speed Quizzer', 'icon' => '⚡', 'category' => 'academics', 'desc' => 'Scored 100% in a live competitive quiz sprint.'],
            ['key' => 'project_architect', 'title' => 'Project Architect', 'icon' => '🚀', 'category' => 'academics', 'desc' => 'Completed and excelled in a capstone case study or project.'],
            ['key' => 'debate_champion', 'title' => 'Debate Laureate', 'icon' => '🏆', 'category' => 'competitions', 'desc' => 'Achieved podium placement in an institutional debate tournament.'],
            ['key' => 'flashcard_master', 'title' => 'Flashcard Virtuoso', 'icon' => '📇', 'category' => 'academics', 'desc' => 'Mastered 10+ chapter flashcard decks with spaced repetition.'],
            ['key' => 'birthday_star', 'title' => 'Birthday Celebrant', 'icon' => '🎂', 'category' => 'milestones', 'desc' => 'Celebrated an academic birthday.'],
            ['key' => 'presidential_fellow', 'title' => 'Presidential Scholar', 'icon' => '👑', 'category' => 'milestones', 'desc' => 'Reached the highest institutional tier.'],
            ['key' => 'bloom_virtuoso', 'title' => 'Bloom Level 6 Conqueror', 'icon' => '🧠', 'category' => 'academics', 'desc' => 'Created original academic solutions at Bloom Evaluate & Create.'],
        ];
        $earnedKeys = $badges->pluck('badge_key')->all();
        $allStudents = collect();
        if ($actor->isAdmin()) {
            $allStudents = User::query()->where('role', 'student')->orderBy('name')->get(['id', 'name', 'student_id_number']);
        } elseif ($actor->isTeacher()) {
            $classIds = $this->assignedClassIds($actor);
            $allStudents = User::query()->where('role', 'student')->where(function ($q) use ($classIds) {
                $q->whereIn('current_class_id', $classIds)
                    ->orWhereHas('classEnrollments', fn ($eq) => $eq->where('status', 'active')->whereIn('class_id', $classIds))
                    ->orWhereHas('batch', fn ($bq) => $bq->whereIn('class_id', $classIds));
            })->orderBy('name')->get(['id', 'name', 'student_id_number']);
        }
        $canIssue = $actor->isAdmin() || $actor->isTeacher();

        return view('certificates.index', compact('viewUser', 'certificates', 'badges', 'allSystemBadges', 'earnedKeys', 'allStudents', 'canIssue'));
    }

    public function showCertificate(UserCertificate $certificate): View
    {
        $actor = Auth::user();
        abort_unless($actor, 401);
        $this->authorizeCertificateView($actor, $certificate);
        $certificate->load(['user', 'issuer']);

        return view('certificates.parchment', compact('certificate'));
    }

    private function authorizeCertificateView(User $actor, UserCertificate $certificate): void
    {
        if ($actor->isAdmin() || (int) $certificate->user_id === (int) $actor->id) {
            return;
        }
        if ($actor->isParent()) {
            $child = User::query()->whereKey($certificate->user_id)->where(function ($q) use ($actor) {
                $q->where('parent_id', $actor->id);
                if ($actor->email) {
                    $q->orWhere(fn ($legacy) => $legacy->whereNull('parent_id')->where('guardian_email', $actor->email));
                }
            })->exists();
            abort_unless($child, 403);

            return;
        }
        if ($actor->isTeacher()) {
            $student = User::findOrFail($certificate->user_id);
            abort_unless($this->teacherCanAccessStudent($actor, $student), 403);

            return;
        }
        abort(403);
    }

    public function verifyCertificate(string $hash): View
    {
        abort_unless(preg_match('/^[a-f0-9]{32}$/i', $hash) === 1, 404);
        $certificate = UserCertificate::with(['user:id,name', 'issuer:id,name'])
            ->where('verification_hash', $hash)
            ->firstOrFail();

        return view('certificates.verify', compact('certificate'));
    }

    public function issueCertificate(Request $request): RedirectResponse
    {
        $actor = Auth::user();
        abort_unless($actor && ($actor->isTeacher() || $actor->isAdmin()), 403);
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'title' => 'required|string|max:255',
            'category' => 'required|in:academic_excellence,olympiad,debate,course_completion,project_mastery',
            'citation' => 'required|string|max:3000',
            'issued_date' => 'required|date|before_or_equal:today',
        ]);
        $student = User::query()->where('role', 'student')->findOrFail($validated['user_id']);
        if ($actor->isTeacher()) {
            abort_unless($this->teacherCanAccessStudent($actor, $student), 403, 'You may only issue certificates to students in your assigned classes.');
        }

        $certificate = DB::transaction(function () use ($validated, $student, $actor) {
            // Random serials avoid count()+1 race conditions under concurrent issuance.
            do {
                $serial = 'CERT-'.now()->format('Y').'-'.Str::upper(Str::random(10));
            } while (UserCertificate::where('certificate_number', $serial)->exists());
            do {
                $hash = hash('sha256', Str::uuid()->toString().Str::random(64));
            } while (UserCertificate::where('verification_hash', $hash)->exists());

            return UserCertificate::create([
                'user_id' => $student->id,
                'issuer_id' => $actor->id,
                'certificate_number' => $serial,
                'title' => $validated['title'],
                'category' => $validated['category'],
                'citation' => $validated['citation'],
                'issued_date' => $validated['issued_date'],
                'verification_hash' => $hash,
            ]);
        });

        return redirect()->route('certificates.show', $certificate->id)
            ->with('success', "Certificate {$certificate->certificate_number} issued successfully.");
    }

    public function destroy(Request $request, UserCertificate $certificate): RedirectResponse
    {
        $actor = Auth::user();
        abort_unless($actor && ($actor->isTeacher() || $actor->isAdmin()), 403);
        if ($actor->isTeacher()) {
            $student = User::findOrFail($certificate->user_id);
            abort_unless($this->teacherCanAccessStudent($actor, $student), 403, 'You may only revoke certificates for students in your assigned classes.');
        }
        if (! $certificate->revoked_at) {
            $validated = $request->validate(['revocation_reason' => 'nullable|string|max:1000']);
            $certificate->forceFill([
                'revoked_at' => now(),
                'revoked_by' => $actor->id,
                'revocation_reason' => $validated['revocation_reason'] ?? null,
            ])->save();
        }

        return redirect()->route('certificates.index')->with('success', "Certificate {$certificate->certificate_number} has been revoked. Its verification record has been retained.");
    }
}
