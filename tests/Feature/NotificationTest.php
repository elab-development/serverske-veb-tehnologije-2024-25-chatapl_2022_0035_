<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Room;
use App\Models\Message;
use App\Notifications\MessageNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_notifications()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->getJson('/api/notifications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'pagination'
            ]);
    }

    public function test_user_can_get_unread_count()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->getJson('/api/notifications/unread-count');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'count'
            ]);
    }

    public function test_user_can_mark_notification_as_read()
    {
        $user = User::factory()->create();
        
        // Create a test notification
        $user->notify(new MessageNotification(
            Message::factory()->create(),
            User::factory()->create()
        ));

        $notification = $user->notifications()->first();
        
        $response = $this->actingAs($user)
            ->patchJson("/api/notifications/{$notification->id}/read");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Notification marked as read'
            ]);
    }

    public function test_user_can_get_notification_preferences()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->getJson('/api/notifications/preferences');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'preferences' => [
                    'email_notifications',
                    'push_notifications',
                    'message_notifications',
                    'room_invitation_notifications',
                    'security_alerts'
                ]
            ]);
    }

    public function test_user_can_update_notification_preferences()
    {
        $user = User::factory()->create();
        
        $preferences = [
            'email_notifications' => false,
            'push_notifications' => true,
            'message_notifications' => false,
            'room_invitation_notifications' => true,
            'security_alerts' => true,
        ];
        
        $response = $this->actingAs($user)
            ->putJson('/api/notifications/preferences', $preferences);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Notification preferences updated'
            ]);
    }

    public function test_user_can_send_test_notification()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->postJson('/api/notifications/test');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Test notification sent'
            ]);
    }

    public function test_user_can_get_notification_statistics()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->getJson('/api/notifications/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'statistics' => [
                    'total_notifications',
                    'unread_notifications',
                    'read_notifications',
                    'notifications_by_type',
                    'recent_notifications'
                ]
            ]);
    }

    public function test_message_notification_is_sent_when_message_is_created()
    {
        Notification::fake();
        
        $user = User::factory()->create();
        $room = Room::factory()->create();
        $room->users()->attach($user->id, ['role' => 'member']);
        
        $message = Message::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id
        ]);
        
        // Trigger notification
        $user->notify(new MessageNotification($message, $user));
        
        Notification::assertSentTo(
            $user,
            MessageNotification::class
        );
    }

    public function test_notification_preferences_are_respected()
    {
        $user = User::factory()->create([
            'message_notifications' => false
        ]);
        
        $this->assertFalse($user->shouldReceiveMessageNotifications());
        
        $user->update(['message_notifications' => true]);
        
        $this->assertTrue($user->shouldReceiveMessageNotifications());
    }
} 