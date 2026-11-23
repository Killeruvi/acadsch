<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Schedule;
use App\Models\StaffPayroll;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffDirectoryController extends Controller
{
    protected function checkAdminAccess(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Staff personal records are restricted to administrators.');
        }
    }

    /**
     * Display paginated staff and faculty directory with role filtering and search.
     */
    public function index(Request $request): View
    {
        $this->checkAdminAccess();

        $searchTerm = trim($request->query('search', ''));
        $roleFilter = $request->query('role', 'all'); // all, teacher, admin
        $statusFilter = $request->query('status', 'all');

        $query = User::whereIn('role', ['teacher', 'admin']);

        if (! empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%")
                    ->orWhere('phone', 'like', "%{$searchTerm}%")
                    ->orWhere('student_id_number', 'like', "%{$searchTerm}%");
            });
        }

        if ($roleFilter !== 'all' && in_array($roleFilter, ['teacher', 'admin'], true)) {
            $query->where('role', $roleFilter);
        }

        if ($statusFilter === 'active') {
            $query->where('is_approved', true);
        } elseif ($statusFilter === 'pending') {
            $query->where('is_approved', false);
        }

        $staffMembers = $query->with(['taughtCourses:id,teacher_id,title,code'])
            ->withCount(['taughtCourses'])
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $totalTeachers = User::where('role', 'teacher')->count();
        $totalAdmins = User::where('role', 'admin')->count();
        $totalApprovedStaff = User::whereIn('role', ['teacher', 'admin'])->where('is_approved', true)->count();
        $totalAssignedCourses = Course::whereNotNull('teacher_id')->count();

        // Safety Captcha numbers for deletion modal
        $captchaNum1 = rand(3, 9);
        $captchaNum2 = rand(2, 8);
        $captchaExpected = $captchaNum1 + $captchaNum2;

        return view('staff.index', compact(
            'staffMembers',
            'searchTerm',
            'roleFilter',
            'statusFilter',
            'totalTeachers',
            'totalAdmins',
            'totalApprovedStaff',
            'totalAssignedCourses',
            'captchaNum1',
            'captchaNum2',
            'captchaExpected'
        ));
    }

    /**
     * Export staff roster and teaching assignments as CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $this->checkAdminAccess();

        $searchTerm = trim($request->query('search', ''));
        $roleFilter = $request->query('role', 'all');
        $statusFilter = $request->query('status', 'all');

        $query = User::whereIn('role', ['teacher', 'admin'])->with(['taughtCourses']);

        if (! empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%")
                    ->orWhere('phone', 'like', "%{$searchTerm}%")
                    ->orWhere('student_id_number', 'like', "%{$searchTerm}%");
            });
        }

        if ($roleFilter !== 'all' && in_array($roleFilter, ['teacher', 'admin'], true)) {
            $query->where('role', $roleFilter);
        }

        if ($statusFilter === 'active') {
            $query->where('is_approved', true);
        } elseif ($statusFilter === 'pending') {
            $query->where('is_approved', false);
        }

        $staff = $query->orderBy('name')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="primearc_faculty_staff_directory_'.date('Y_m_d_His').'.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($staff) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, [
                'Staff ID',
                'Full Name',
                'Designated Role',
                'Official Email',
                'Phone Contact',
                'Account Status',
                'Courses Taught Count',
                'Assigned Course Codes',
                'Joined Date',
            ]);

            foreach ($staff as $s) {
                $coursesList = $s->taughtCourses->pluck('code')->filter()->implode(', ');
                fputcsv($handle, [
                    $s->student_id_number ?? ('PA-'.($s->role === 'admin' ? 'ADM' : 'FAC').'-'.str_pad($s->id, 4, '0', STR_PAD_LEFT)),
                    $s->name,
                    ucfirst($s->role),
                    $s->email,
                    $s->phone ?? 'N/A',
                    $s->is_approved ? 'Approved' : 'Pending Verification',
                    $s->taughtCourses->count(),
                    $coursesList ?: 'None Assigned',
                    $s->created_at ? $s->created_at->format('Y-m-d') : 'N/A',
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Show staff member dossier and instructional courses.
     */
    public function show(User $staff): View
    {
        $this->checkAdminAccess();

        if (! in_array($staff->role, ['teacher', 'admin'], true)) {
            return redirect()->route('students.show', $staff->id);
        }

        $courses = Course::where('teacher_id', $staff->id)->get();
        $schedules = Schedule::whereIn('course_id', $courses->pluck('id'))->get();

        // Safety Captcha numbers for deletion modal
        $captchaNum1 = rand(3, 9);
        $captchaNum2 = rand(2, 8);
        $captchaExpected = $captchaNum1 + $captchaNum2;

        return view('staff.show', compact('staff', 'courses', 'schedules', 'captchaNum1', 'captchaNum2', 'captchaExpected'));
    }

    /**
     * Form to edit staff member details and credentials.
     */
    public function edit(User $staff): View
    {
        $user = Auth::user();
        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Unauthorized. Only administrators can edit faculty profiles.');
        }

        if (! in_array($staff->role, ['teacher', 'admin'], true)) {
            return redirect()->route('students.edit', $staff->id);
        }

        return view('staff.edit', compact('staff'));
    }

    /**
     * Update staff member details.
     */
    public function update(Request $request, User $staff): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Unauthorized. Only administrators can update faculty profiles.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$staff->id,
            'phone' => 'nullable|string|max:30',
            'role' => 'required|in:teacher,admin',
            'is_approved' => 'nullable|boolean',
        ]);

        if ($staff->id === $user->id && $validated['role'] !== 'admin') {
            return back()->withInput()->withErrors(['role' => 'You cannot remove your own administrator role.']);
        }

        $nextApproved = $request->has('is_approved') ? $request->boolean('is_approved') : (bool) $staff->is_approved;
        $willRemainActiveAdmin = $staff->role === 'admin' && $staff->is_approved
            && ($validated['role'] !== 'admin' || ! $nextApproved);
        if ($willRemainActiveAdmin && User::where('role', 'admin')->where('is_approved', true)->count() <= 1) {
            return back()->withInput()->withErrors(['role' => 'The institution must retain at least one approved administrator.']);
        }

        $staff->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'is_approved' => $request->has('is_approved') ? $request->boolean('is_approved') : $staff->is_approved,
        ]);

        return redirect()->route('staff.show', $staff->id)
            ->with('success', "Faculty dossier for {$staff->name} updated successfully.");
    }

    /**
     * Delete staff member permanently with confirmation caution and password / captcha protection.
     */
    public function destroy(Request $request, User $staff): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Administrator privileges required for staff account termination.');
        }

        if ($staff->id === $user->id) {
            return back()->withErrors(['admin_password' => 'Self-account deletion is prohibited by institutional security policy.']);
        }

        $request->validate([
            'admin_password' => 'required|string',
            'confirmation_text' => 'required|string',
            'captcha_expected' => 'required|integer',
            'captcha_answer' => 'required|integer',
        ]);

        // 1. Keyword check
        if (trim(strtoupper($request->input('confirmation_text'))) !== 'DELETE') {
            return back()->withErrors(['confirmation_text' => 'You must type DELETE in all capitals to authorize deletion.']);
        }

        // 2. Administrator password check
        if (! Hash::check($request->input('admin_password'), $user->password)) {
            return back()->withErrors(['admin_password' => 'Incorrect administrative password. Deletion transaction aborted.']);
        }

        // 3. Mathematical Captcha check
        if ((int) $request->input('captcha_expected') !== (int) $request->input('captcha_answer')) {
            return back()->withErrors(['captcha_answer' => 'Security calculation verification mismatch. Accidental deletion guard triggered.']);
        }

        if (StaffPayroll::where('staff_id', $staff->id)->exists()) {
            return back()->withErrors(['staff' => 'This staff account has payroll history. Deactivate the account instead; payroll records must not be cascade-deleted.']);
        }
        if (DB::table('staff_employment_profiles')->where('staff_id', $staff->id)->exists()
            || DB::table('staff_attendance_records')->where('staff_id', $staff->id)->exists()
            || DB::table('staff_leave_requests')->where('staff_id', $staff->id)->exists()) {
            return back()->withErrors(['staff' => 'This staff account has employment or HR history. Use the controlled separation workflow; historical records must be retained.']);
        }

        if ($staff->role === 'admin' && $staff->is_approved
            && User::where('role', 'admin')->where('is_approved', true)->count() <= 1) {
            return back()->withErrors(['staff' => 'The last approved administrator account cannot be deleted.']);
        }

        $staffName = $staff->name;
        $staffRole = ucfirst($staff->role);

        $staff->delete();

        return redirect()->route('staff.index')
            ->with('success', "Staff account [{$staffName} &bull; {$staffRole}] has been permanently deleted after verified administrator credential confirmation.");
    }
}
