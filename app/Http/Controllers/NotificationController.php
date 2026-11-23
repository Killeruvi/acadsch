<?php

namespace App\Http\Controllers;

use App\Models\InAppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * View all user notifications with filter by type.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        $query = InAppNotification::where('user_id', $user->id);

        if ($request->filled('type') && $request->query('type') !== 'all') {
            $type = $request->validate([
                'type' => 'required|string|in:general,assignment,bloom,blooms,fee,fees,dpp,homework,attendance,notice,leave,exam,transport',
            ])['type'];
            $normalizedType = match($type) {
                'blooms', 'bloom' => 'bloom',
                'fees', 'fee' => 'fee',
                default => $type,
            };
            $query->where('type', $normalizedType);
        }

        if ($request->query('filter') === 'unread') {
            $query->where('is_read', false);
        }

        $notifications = $query->latest('created_at')->paginate(15)->withQueryString();
        $unreadCount = InAppNotification::where('user_id', $user->id)->where('is_read', false)->count();
        $readCount = InAppNotification::where('user_id', $user->id)->where('is_read', true)->count();

        return view('notifications.index', compact('notifications', 'unreadCount', 'readCount'));
    }

    /**
     * Mark single notification as read and redirect to its action URL.
     */
    public function markAsRead(int $id): RedirectResponse
    {
        $notif = InAppNotification::where('user_id', Auth::id())->findOrFail($id);
        $notif->markAsRead();

        if ($notif->action_url && $this->isSafeInternalUrl($notif->action_url)) {
            return redirect()->to($notif->action_url);
        }

        return back()->with('success', 'Notification marked as read.');
    }

    /**
     * Mark all user notifications as read.
     */
    public function markAllRead(): RedirectResponse
    {
        InAppNotification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Delete a single notification.
     */
    public function destroy(int $id): RedirectResponse
    {
        $notif = InAppNotification::where('user_id', Auth::id())->findOrFail($id);
        $notif->delete();

        return back()->with('success', 'Notification deleted successfully.');
    }

    /**
     * Bulk delete all read notifications.
     */
    public function deleteRead(): RedirectResponse
    {
        $deleted = InAppNotification::where('user_id', Auth::id())
            ->where('is_read', true)
            ->delete();

        return back()->with('success', "{$deleted} read notification" . ($deleted === 1 ? '' : 's') . " deleted successfully.");
    }

    /**
     * Bulk delete selected notifications.
     */
    public function bulkDelete(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|max:100',
            'ids.*' => 'required|integer|distinct|min:1',
        ]);

        $deleted = InAppNotification::where('user_id', Auth::id())
            ->whereIn('id', $validated['ids'])
            ->delete();

        return back()->with('success', "{$deleted} notification" . ($deleted === 1 ? '' : 's') . " deleted successfully.");
    }

    /**
     * JSON API for navbar polling or dynamic dropdown.
     */
    public function getUnread(): JsonResponse
    {
        $user = Auth::user();
        $unreadCount = InAppNotification::where('user_id', $user->id)->where('is_read', false)->count();
        $notifs = InAppNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->latest('created_at')
            ->take(5)
            ->get();

        return response()->json([
            'unread_count' => $unreadCount,
            'notifications' => $notifs,
        ]);
    }

    /** Reject external, protocol-relative and malformed notification destinations. */
    private function isSafeInternalUrl(string $url): bool
    {
        $url = trim($url);
        if ($url === '' || str_starts_with($url, '//') || str_contains($url, '\\')) {
            return false;
        }

        $parts = parse_url($url);
        if ($parts === false || isset($parts['scheme']) || isset($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }

        return str_starts_with($url, '/') && !str_starts_with($url, '/\\');
    }

}
