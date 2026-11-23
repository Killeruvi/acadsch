<?php

namespace App\Http\Controllers;

use App\Models\FacultyAccessCode;
use App\Models\FacultyDeviceLock;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FacultyCodeController extends Controller
{
    /**
     * Display faculty authorization codes, device locks, and teacher approval panel.
     */
    public function index(): View
    {
        $user = Auth::user();
        if (! $user->isAdmin()) {
            abort(403, 'Unauthorized. Only institutional administrators can manage faculty authorization codes.');
        }

        $unusedCodes = FacultyAccessCode::with('generator')
            ->where('is_used', false)
            ->latest()
            ->paginate(10, ['*'], 'unused_page')
            ->withQueryString();

        $usedCodes = FacultyAccessCode::with(['generator', 'teacher'])
            ->where('is_used', true)
            ->latest('used_at')
            ->paginate(10, ['*'], 'used_page')
            ->withQueryString();

        $deviceLocks = FacultyDeviceLock::with(['user', 'code'])
            ->latest()
            ->paginate(10, ['*'], 'locks_page')
            ->withQueryString();

        $teachers = User::where('role', 'teacher')
            ->latest()
            ->paginate(10, ['*'], 'teachers_page')
            ->withQueryString();

        return view('admin.faculty-codes.index', [
            'unusedCodes' => $unusedCodes,
            'usedCodes' => $usedCodes,
            'deviceLocks' => $deviceLocks,
            'teachers' => $teachers,
        ]);
    }

    /**
     * Manually generate a new faculty authorization code.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->isAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:255',
        ]);

        $code = FacultyAccessCode::createUniqueCode($user->id, $validated['notes'] ?? null);

        return back()->with('success', "Faculty authorization code [{$code->code}] generated successfully!");
    }

    /**
     * Revoke / delete an unused faculty authorization code.
     */
    public function destroy(FacultyAccessCode $code): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->isAdmin()) {
            abort(403, 'Unauthorized.');
        }

        if ($code->is_used) {
            return back()->with('error', 'Cannot revoke a code that has already been redeemed by a faculty member.');
        }

        $codeStr = $code->code;
        $code->delete();

        return back()->with('success', "Faculty code {$codeStr} has been revoked.");
    }

    /**
     * Toggle lock / approval status for a teacher account.
     */
    public function toggleTeacherLock(User $user): RedirectResponse
    {
        $admin = Auth::user();
        if (! $admin->isAdmin()) {
            abort(403, 'Unauthorized.');
        }

        if (! $user->isTeacher()) {
            return back()->with('error', 'Target user is not a teacher.');
        }

        $newStatus = ! $user->is_approved;
        $user->update([
            'is_approved' => $newStatus,
            'approved_by' => $newStatus ? $admin->id : null,
            'approved_at' => $newStatus ? now() : null,
        ]);

        $statusText = $newStatus ? 'Approved & Unlocked' : 'Locked';

        return back()->with('success', "Teacher {$user->name} is now {$statusText}.");
    }

    /**
     * Clear a hardware device lock to permit a new request.
     */
    public function unlockDevice(FacultyDeviceLock $lock): RedirectResponse
    {
        $admin = Auth::user();
        if (! $admin->isAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $lock->delete();

        return back()->with('success', 'Device lock removed. A new teacher request may now originate from this device.');
    }
}
