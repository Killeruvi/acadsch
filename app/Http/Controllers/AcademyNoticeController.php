<?php

namespace App\Http\Controllers;

use App\Models\AcademyNotice;
use App\Models\CircularReadReceipt;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AcademyNoticeController extends Controller
{
    /**
     * Notice board + Circulars — tabs on one page, no reload.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        try {
            $user->recordDailyActivity('visit');
        } catch (\Throwable $e) {
        }

        $tab = $request->query('tab', 'notices'); // notices | circulars | drafts | pending

        // Build query for published notices visible to this user
        $baseQuery = AcademyNotice::with('author:id,name,role')
            ->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        // Role-based audience filter (server-side)
        if ($user->isStudent()) {
            $baseQuery->whereIn('target_audience', ['all', 'students']);
        } elseif ($user->isParent()) {
            $baseQuery->whereIn('target_audience', ['all', 'parents']);
        } elseif ($user->isTeacher()) {
            $baseQuery->whereIn('target_audience', ['all', 'teachers', 'staff']);
        } elseif ($user->isCashier()) {
            $baseQuery->whereIn('target_audience', ['all', 'staff']);
        }
        // Admin sees everything

        $urgentCount = (clone $baseQuery)->where('priority', 'urgent')->count();
        $totalNotices = (clone $baseQuery)->where('type', 'notice')->count();
        $totalCirculars = (clone $baseQuery)->where('type', 'circular')->count();

        // Apply tab filter
        $listQuery = clone $baseQuery;
        if ($tab === 'circulars') {
            $listQuery->where('type', 'circular');
        } else {
            $listQuery->where('type', 'notice');
        }

        if ($request->filled('priority') && $request->query('priority') !== 'all') {
            $p = $request->validate(['priority' => 'required|in:normal,high,urgent'])['priority'];
            $listQuery->where('priority', $p);
        }

        $notices = $listQuery->paginate(12)->withQueryString();

        // For each item, check if current user has read it
        $readIds = [];
        if ($notices->count()) {
            $readIds = CircularReadReceipt::where('user_id', $user->id)
                ->whereIn('notice_id', $notices->pluck('id'))
                ->pluck('notice_id')
                ->toArray();
            // Auto-mark as read when listing (passive read receipt)
            $unread = $notices->pluck('id')->diff($readIds)->values();
            if ($unread->count()) {
                foreach ($unread as $nid) {
                    CircularReadReceipt::firstOrCreate(
                        ['notice_id' => $nid, 'user_id' => $user->id],
                        ['read_at' => now(), 'acknowledged' => false]
                    );
                }
                $readIds = array_merge($readIds, $unread->toArray());
            }
        }

        // Drafts & pending approval — only visible to admin/author
        $drafts = collect();
        $pendingApproval = collect();
        if ($user->isAdmin()) {
            $drafts = AcademyNotice::where('status', 'draft')
                ->with('author:id,name')->latest()->limit(20)->get();
            $pendingApproval = AcademyNotice::where('status', 'pending_approval')
                ->with('author:id,name')->latest()->limit(20)->get();
        } elseif ($user->isTeacher()) {
            $drafts = AcademyNotice::where('status', 'draft')
                ->where('author_id', $user->id)
                ->latest()->limit(10)->get();
            $pendingApproval = AcademyNotice::where('status', 'pending_approval')
                ->where('author_id', $user->id)
                ->latest()->limit(10)->get();
        }

        return view('notices.index', compact(
            'notices', 'urgentCount', 'totalNotices', 'totalCirculars',
            'tab', 'readIds', 'drafts', 'pendingApproval'
        ));
    }

    /**
     * Create a notice or circular (admin: direct publish; teacher: submit for approval).
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || (! $user->isAdmin() && ! $user->isTeacher())) {
            abort(403, 'Unauthorized. Only teachers and administrators can create notices and circulars.');
        }

        $validated = $request->validate([
            'type' => 'required|in:notice,circular',
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:20000',
            'priority' => 'required|in:normal,high,urgent',
            'target_audience' => 'required|in:all,students,teachers,parents,staff',
            'is_pinned' => 'nullable|boolean',
            'requires_acknowledgement' => 'nullable|boolean',
            'expires_at' => 'nullable|date|after:today',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        // Teachers: students-only notices, must go through approval for circulars
        if ($user->isTeacher()) {
            if ($validated['type'] === 'circular') {
                abort(403, 'Teachers must submit circulars for admin approval; use the notice type for direct student notices.');
            }
            if (! in_array($validated['target_audience'], ['students'], true)) {
                abort(403, 'Teachers may publish notices for students only.');
            }
        }

        // Determine initial status
        $status = $user->isAdmin() ? 'published' : 'pending_approval';
        $publishedAt = now();
        $approvedBy = $user->isAdmin() ? $user->id : null;
        $approvedAt = $user->isAdmin() ? now() : null;

        // Auto-generate circular number for published circulars
        $circularNumber = null;
        if ($validated['type'] === 'circular' && $user->isAdmin()) {
            $year = now()->format('Y');
            $count = AcademyNotice::where('type', 'circular')
                ->whereYear('created_at', $year)
                ->count() + 1;
            $circularNumber = 'CIR/'.$year.'/'.str_pad($count, 3, '0', STR_PAD_LEFT);
        }

        $attachmentUrl = null;
        if ($request->hasFile('attachment')) {
            $attachmentUrl = $request->file('attachment')->store('notices/attachments', 'public');
        }

        $notice = AcademyNotice::create([
            'type' => $validated['type'],
            'author_id' => $user->id,
            'approved_by' => $approvedBy,
            'approved_at' => $approvedAt,
            'circular_number' => $circularNumber,
            'title' => $validated['title'],
            'content' => $validated['content'],
            'priority' => $validated['priority'],
            'target_audience' => $validated['target_audience'],
            'is_pinned' => ! empty($validated['is_pinned']),
            'requires_acknowledgement' => ! empty($validated['requires_acknowledgement']),
            'expires_at' => $validated['expires_at'] ?? null,
            'status' => $status,
            'published_at' => $publishedAt,
            'attachment_url' => $attachmentUrl,
        ]);

        if ($notice->isPublished()) {
            $this->broadcastNotice($notice);
        } elseif ($notice->isPendingApproval()) {
            // Notify admins of pending approval
            $adminIds = User::where('role', 'admin')->pluck('id')->toArray();
            if (! empty($adminIds)) {
                NotificationService::notifyUsers(
                    $adminIds,
                    'circular_pending',
                    "Circular Approval Needed: {$notice->title}",
                    "Submitted by {$user->name}. Review and approve or reject.",
                    route('notices.index', ['tab' => 'pending']),
                    '📋'
                );
            }
        }

        $msg = $notice->isPublished()
            ? "'{$notice->title}' published and broadcast."
            : "'{$notice->title}' submitted for admin approval.";

        return redirect()->route('notices.index')->with('success', $msg);
    }

    /**
     * Admin approves a pending circular/notice.
     */
    public function approve(int $id): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Only administrators can approve notices.');
        }

        $notice = AcademyNotice::where('status', 'pending_approval')->findOrFail($id);

        // Generate circular number on approval
        $circularNumber = $notice->circular_number;
        if ($notice->type === 'circular' && ! $circularNumber) {
            $year = now()->format('Y');
            $count = AcademyNotice::where('type', 'circular')
                ->whereYear('created_at', $year)
                ->where('status', 'published')
                ->count() + 1;
            $circularNumber = 'CIR/'.$year.'/'.str_pad($count, 3, '0', STR_PAD_LEFT);
        }

        $notice->update([
            'status' => 'published',
            'published_at' => now(),
            'approved_by' => $user->id,
            'approved_at' => now(),
            'circular_number' => $circularNumber,
        ]);

        $this->broadcastNotice($notice);

        return redirect()->back()->with('success', "Notice approved and published: '{$notice->title}'.");
    }

    /**
     * Admin rejects a pending notice.
     */
    public function reject(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Only administrators can reject notices.');
        }

        $notice = AcademyNotice::where('status', 'pending_approval')->findOrFail($id);
        $reason = $request->validate(['reason' => 'nullable|string|max:500'])['reason'] ?? 'No reason provided.';

        $notice->update(['status' => 'draft']);

        // Notify author
        try {
            NotificationService::notifyUser(
                $notice->author_id,
                'circular_rejected',
                "Notice Returned: {$notice->title}",
                "Your notice was returned by admin. Reason: {$reason}",
                route('notices.index', ['tab' => 'drafts']),
                '📋'
            );
        } catch (\Throwable $e) {
        }

        return redirect()->back()->with('info', "Notice returned to draft: '{$notice->title}'.");
    }

    /**
     * Mark a circular as read + optionally acknowledged by the current user.
     */
    public function markRead(Request $request, int $id)
    {
        $user = Auth::user();
        $notice = AcademyNotice::where('status', 'published')->findOrFail($id);

        $acknowledged = (bool) ($request->input('acknowledge', false) || $request->input('acknowledged', false));

        $receipt = CircularReadReceipt::firstOrNew(
            ['notice_id' => $notice->id, 'user_id' => $user->id]
        );

        if (! $receipt->exists) {
            $receipt->read_at = now();
        }

        if ($acknowledged && ! $receipt->acknowledged) {
            $receipt->acknowledged = true;
            $receipt->acknowledged_at = now();
        }

        $receipt->save();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'read' => true,
                'acknowledged' => (bool) $receipt->acknowledged,
            ]);
        }

        return redirect()->back()->with('success', 'Marked as read.');
    }

    /**
     * Read receipt stats for a circular — admin/author only.
     */
    public function readStats(int $id): JsonResponse
    {
        $user = Auth::user();
        $notice = AcademyNotice::findOrFail($id);

        if (! $user->isAdmin() && $user->id !== $notice->author_id) {
            abort(403);
        }

        $totalRecipients = match ($notice->target_audience) {
            'students' => User::where('role', 'student')->count(),
            'teachers' => User::where('role', 'teacher')->count(),
            'parents' => User::where('role', 'parent')->count(),
            'staff' => User::whereIn('role', ['teacher', 'cashier'])->count(),
            default => User::whereIn('role', ['student', 'teacher', 'parent', 'cashier', 'admin'])->count(),
        };

        $readCount = CircularReadReceipt::where('notice_id', $notice->id)->count();
        $ackCount = CircularReadReceipt::where('notice_id', $notice->id)->where('acknowledged', true)->count();

        return response()->json([
            'total_recipients' => $totalRecipients,
            'read_count' => $readCount,
            'ack_count' => $ackCount,
            'unread_count' => max(0, $totalRecipients - $readCount),
            'read_percent' => $totalRecipients > 0 ? round($readCount / $totalRecipients * 100) : 0,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $notice = AcademyNotice::findOrFail($id);
        $user = Auth::user();

        if (! $user->isAdmin() && $user->id !== $notice->author_id) {
            abort(403, 'Unauthorized.');
        }
        // Can only edit draft or pending_approval notices
        if ($notice->isPublished() && ! $user->isAdmin()) {
            abort(403, 'Published notices can only be edited by administrators.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:20000',
            'priority' => 'required|in:normal,high,urgent',
            'target_audience' => 'required|in:all,students,teachers,parents,staff',
            'is_pinned' => 'nullable|boolean',
            'requires_acknowledgement' => 'nullable|boolean',
            'expires_at' => 'nullable|date',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        if ($user->isTeacher() && ! in_array($validated['target_audience'], ['students'], true)) {
            abort(403, 'Teachers may publish notices for students only.');
        }

        $updateData = [
            'title' => $validated['title'],
            'content' => $validated['content'],
            'priority' => $validated['priority'],
            'target_audience' => $validated['target_audience'],
            'is_pinned' => ! empty($validated['is_pinned']),
            'requires_acknowledgement' => ! empty($validated['requires_acknowledgement']),
            'expires_at' => $validated['expires_at'] ?? null,
        ];

        if ($request->hasFile('attachment')) {
            if ($notice->attachment_url && Storage::disk('public')->exists($notice->attachment_url)) {
                Storage::disk('public')->delete($notice->attachment_url);
            }
            $updateData['attachment_url'] = $request->file('attachment')->store('notices/attachments', 'public');
        }

        $notice->update($updateData);

        return redirect()->route('notices.index')->with('success', "Notice '{$notice->title}' updated.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $notice = AcademyNotice::findOrFail($id);
        $user = Auth::user();

        if (! $user->isAdmin() && $user->id !== $notice->author_id) {
            abort(403, 'Unauthorized.');
        }

        $notice->delete();

        return redirect()->route('notices.index')->with('success', 'Notice removed from board.');
    }

    // ─── private helpers ────────────────────────────────────────────

    private function broadcastNotice(AcademyNotice $notice): void
    {
        $icon = match ($notice->priority) {
            'urgent' => '🚨',
            'high' => '📢',
            default => $notice->type === 'circular' ? '📋' : '📌',
        };

        try {
            NotificationService::broadcastNotice(
                $notice->title,
                substr(strip_tags($notice->content), 0, 120).'…',
                route('notices.index'),
                $icon,
                $notice->target_audience
            );
        } catch (\Throwable $e) {
        }
    }
}
