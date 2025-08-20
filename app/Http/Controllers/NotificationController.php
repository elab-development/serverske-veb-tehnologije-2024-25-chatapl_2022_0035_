<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use App\Models\User;
use App\Models\Room;
use App\Models\Message;
use App\Notifications\MessageNotification;
use App\Notifications\RoomInvitationNotification;
use App\Notifications\SecurityAlertNotification;

class NotificationController extends Controller
{
    /**
     * Get user's notifications with pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $perPage = $request->get('per_page', 15);
        
        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $notifications->items(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ]
        ]);
    }

    /**
     * Get unread notifications count.
     */
    public function unreadCount(): JsonResponse
    {
        $user = Auth::user();
        $count = $user->unreadNotifications()->count();

        return response()->json([
            'success' => true,
            'count' => $count
        ]);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(string $id): JsonResponse
    {
        $user = Auth::user();
        $notification = $user->notifications()->findOrFail($id);
        
        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(): JsonResponse
    {
        $user = Auth::user();
        $user->unreadNotifications()->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read'
        ]);
    }

    /**
     * Delete a notification.
     */
    public function destroy(string $id): JsonResponse
    {
        $user = Auth::user();
        $notification = $user->notifications()->findOrFail($id);
        
        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted'
        ]);
    }

    /**
     * Get notification preferences.
     */
    public function preferences(): JsonResponse
    {
        $user = Auth::user();
        
        $preferences = [
            'email_notifications' => $user->email_notifications ?? true,
            'push_notifications' => $user->push_notifications ?? true,
            'message_notifications' => $user->message_notifications ?? true,
            'room_invitation_notifications' => $user->room_invitation_notifications ?? true,
            'security_alerts' => $user->security_alerts ?? true,
        ];

        return response()->json([
            'success' => true,
            'preferences' => $preferences
        ]);
    }

    /**
     * Update notification preferences.
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $request->validate([
            'email_notifications' => 'boolean',
            'push_notifications' => 'boolean',
            'message_notifications' => 'boolean',
            'room_invitation_notifications' => 'boolean',
            'security_alerts' => 'boolean',
        ]);

        $user = Auth::user();
        
        $user->update([
            'email_notifications' => $request->email_notifications,
            'push_notifications' => $request->push_notifications,
            'message_notifications' => $request->message_notifications,
            'room_invitation_notifications' => $request->room_invitation_notifications,
            'security_alerts' => $request->security_alerts,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notification preferences updated',
            'preferences' => $request->all()
        ]);
    }

    /**
     * Send test notification.
     */
    public function sendTest(): JsonResponse
    {
        $user = Auth::user();
        
        // Send a test message notification
        $testMessage = new Message();
        $testMessage->id = 0;
        $testMessage->content = 'This is a test notification';
        $testMessage->room_id = 1;
        $testMessage->room = (object)['name' => 'Test Room'];
        $testMessage->created_at = now();
        
        $user->notify(new MessageNotification($testMessage, $user));

        return response()->json([
            'success' => true,
            'message' => 'Test notification sent'
        ]);
    }

    /**
     * Send bulk notifications to room members.
     */
    public function sendBulkNotification(Request $request): JsonResponse
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'message' => 'required|string|max:500',
            'type' => 'required|in:message,announcement,alert'
        ]);

        $room = Room::findOrFail($request->room_id);
        $sender = Auth::user();
        
        // Check if user has permission to send bulk notifications
        if (!$sender->hasRole('admin') && !$sender->hasRole('moderator')) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions'
            ], 403);
        }

        $users = $room->users()->where('user_id', '!=', $sender->id)->get();
        
        foreach ($users as $user) {
            if ($user->message_notifications) {
                $user->notify(new MessageNotification(
                    (object)[
                        'id' => 0,
                        'content' => $request->message,
                        'room_id' => $room->id,
                        'room' => $room,
                        'created_at' => now()
                    ],
                    $sender
                ));
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Bulk notification sent to ' . $users->count() . ' users'
        ]);
    }

    /**
     * Get notification statistics.
     */
    public function statistics(): JsonResponse
    {
        $user = Auth::user();
        
        $stats = [
            'total_notifications' => $user->notifications()->count(),
            'unread_notifications' => $user->unreadNotifications()->count(),
            'read_notifications' => $user->readNotifications()->count(),
            'notifications_by_type' => [
                'message' => $user->notifications()->where('data->type', 'message')->count(),
                'room_invitation' => $user->notifications()->where('data->type', 'room_invitation')->count(),
                'security_alert' => $user->notifications()->where('data->type', 'security_alert')->count(),
            ],
            'recent_notifications' => $user->notifications()
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
        ];

        return response()->json([
            'success' => true,
            'statistics' => $stats
        ]);
    }
} 