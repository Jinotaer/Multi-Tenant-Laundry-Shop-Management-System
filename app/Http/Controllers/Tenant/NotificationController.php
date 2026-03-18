<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Display the authenticated user's notifications.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $notifications = $user->notifications()->latest()->paginate(15);
        $unreadCount = $user->unreadNotifications()->count();

        return view('tenant.notifications.index', compact('notifications', 'unreadCount'));
    }

    /**
     * Return the authenticated user's recent notifications for the topbar dropdown.
     */
    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();
        $notifications = $user->notifications()->latest()->limit(5)->get();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $notifications->map(
                fn (DatabaseNotification $notification): array => $this->transformNotification($notification)
            )->all(),
        ]);
    }

    /**
     * Mark all unread notifications for the current user as read.
     */
    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Notifications marked as read.');
    }

    /**
     * Normalize notification payloads for the UI.
     *
     * @return array<string, mixed>
     */
    private function transformNotification(DatabaseNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'title' => $notification->data['title'] ?? 'Notification',
            'body' => $notification->data['body'] ?? '',
            'url' => $notification->data['url'] ?? route('tenant.notifications.index', absolute: false),
            'category' => $notification->data['category'] ?? 'general',
            'is_read' => $notification->read_at !== null,
            'created_at' => $notification->created_at?->diffForHumans(),
        ];
    }
}
