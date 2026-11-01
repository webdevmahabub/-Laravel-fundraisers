<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * List all notifications for the authenticated user, paginated.
     */
    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $unreadCount = $request->user()->unreadNotifications()->count();

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    /**
     * Mark a single notification as read and redirect to its target URL.
     * The target is derived from the notification data — never from request input.
     */
    public function markRead(Request $request, string $notificationId): RedirectResponse
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($notificationId);

        $notification->markAsRead();

        $redirect = $this->resolveRedirect($notification->data);

        return redirect()->to($redirect);
    }

    /**
     * Mark all of the authenticated user's unread notifications as read.
     */
    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return redirect()->route('notifications.index')
            ->with('success', 'All notifications marked as read.');
    }

    /**
     * Return the unread notification count as JSON (used by the topbar bell).
     */
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Resolve a safe internal redirect URL from notification data.
     * Falls back to the notifications list — never follows external URLs.
     */
    private function resolveRedirect(array $data): string
    {
        $type = $data['type'] ?? '';

        return match (true) {
            isset($data['campaign_slug']) => route('campaigns.show', $data['campaign_slug']),

            $type === 'organizer_application_approved',
            $type === 'organizer_application_rejected',
            $type === 'organizer_application_submitted' => route('organizer.application.show'),

            isset($data['withdrawal_id']) => route('organizer.withdrawals.index'),

            default => route('notifications.index'),
        };
    }
}
