<?php

namespace App\Services;

use App\Models\User;
use App\Models\Room;
use App\Models\Message;
use App\Notifications\MessageNotification;
use App\Notifications\RoomInvitationNotification;
use App\Notifications\SecurityAlertNotification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send message notification to room members.
     */
    public function sendMessageNotification(Message $message): void
    {
        try {
            $sender = $message->user;
            $room = $message->room;
            
            // Get all users in the room except the sender
            $users = $room->users()
                ->where('user_id', '!=', $sender->id)
                ->where('message_notifications', true)
                ->get();

            foreach ($users as $user) {
                if ($user->shouldReceiveMessageNotifications()) {
                    $user->notify(new MessageNotification($message, $sender));
                }
            }

            Log::info("Message notifications sent to {$users->count()} users for message {$message->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send message notifications: " . $e->getMessage());
        }
    }

    /**
     * Send room invitation notification.
     */
    public function sendRoomInvitationNotification(Room $room, User $inviter, User $invitee): void
    {
        try {
            if ($invitee->shouldReceiveRoomInvitationNotifications()) {
                $invitee->notify(new RoomInvitationNotification($room, $inviter));
                Log::info("Room invitation sent to user {$invitee->id} for room {$room->id}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to send room invitation notification: " . $e->getMessage());
        }
    }

    /**
     * Send security alert notification.
     */
    public function sendSecurityAlertNotification(User $user, string $alertType, array $details, string $ipAddress = null): void
    {
        try {
            if ($user->shouldReceiveSecurityAlerts()) {
                $user->notify(new SecurityAlertNotification($alertType, $details, $ipAddress));
                Log::info("Security alert sent to user {$user->id} for alert type: {$alertType}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to send security alert notification: " . $e->getMessage());
        }
    }

    /**
     * Send bulk notifications to multiple users.
     */
    public function sendBulkNotifications(array $userIds, string $notificationClass, array $data): void
    {
        try {
            $users = User::whereIn('id', $userIds)->get();
            
            foreach ($users as $user) {
                // Check user preferences before sending
                if ($this->shouldSendNotificationToUser($user, $notificationClass)) {
                    $user->notify(new $notificationClass(...$data));
                }
            }

            Log::info("Bulk notifications sent to " . count($users) . " users");
        } catch (\Exception $e) {
            Log::error("Failed to send bulk notifications: " . $e->getMessage());
        }
    }

    /**
     * Check if notification should be sent to user based on preferences.
     */
    private function shouldSendNotificationToUser(User $user, string $notificationClass): bool
    {
        return match($notificationClass) {
            MessageNotification::class => $user->shouldReceiveMessageNotifications(),
            RoomInvitationNotification::class => $user->shouldReceiveRoomInvitationNotifications(),
            SecurityAlertNotification::class => $user->shouldReceiveSecurityAlerts(),
            default => true,
        };
    }

    /**
     * Get notification statistics for a user.
     */
    public function getUserNotificationStats(User $user): array
    {
        return [
            'total_notifications' => $user->notifications()->count(),
            'unread_notifications' => $user->unreadNotifications()->count(),
            'read_notifications' => $user->readNotifications()->count(),
            'notifications_by_type' => [
                'message' => $user->notifications()->where('data->type', 'message')->count(),
                'room_invitation' => $user->notifications()->where('data->type', 'room_invitation')->count(),
                'security_alert' => $user->notifications()->where('data->type', 'security_alert')->count(),
            ],
            'preferences' => $user->getNotificationPreferences(),
        ];
    }

    /**
     * Clean up old notifications.
     */
    public function cleanupOldNotifications(int $daysOld = 30): int
    {
        try {
            $deletedCount = \DB::table('notifications')
                ->where('created_at', '<', now()->subDays($daysOld))
                ->delete();

            Log::info("Cleaned up {$deletedCount} old notifications");
            return $deletedCount;
        } catch (\Exception $e) {
            Log::error("Failed to cleanup old notifications: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllNotificationsAsRead(User $user): int
    {
        try {
            $updatedCount = $user->unreadNotifications()->update(['read_at' => now()]);
            Log::info("Marked {$updatedCount} notifications as read for user {$user->id}");
            return $updatedCount;
        } catch (\Exception $e) {
            Log::error("Failed to mark notifications as read: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get notification summary for dashboard.
     */
    public function getNotificationSummary(): array
    {
        try {
            return [
                'total_notifications' => \DB::table('notifications')->count(),
                'unread_notifications' => \DB::table('notifications')->whereNull('read_at')->count(),
                'notifications_today' => \DB::table('notifications')
                    ->whereDate('created_at', today())
                    ->count(),
                'notifications_this_week' => \DB::table('notifications')
                    ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                    ->count(),
                'top_notification_types' => \DB::table('notifications')
                    ->selectRaw('JSON_EXTRACT(data, "$.type") as type, COUNT(*) as count')
                    ->groupBy('type')
                    ->orderBy('count', 'desc')
                    ->limit(5)
                    ->get(),
            ];
        } catch (\Exception $e) {
            Log::error("Failed to get notification summary: " . $e->getMessage());
            return [];
        }
    }
} 