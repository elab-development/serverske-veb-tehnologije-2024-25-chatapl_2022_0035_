<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use App\Models\Room;
use App\Models\User;

class RoomInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $room;
    protected $inviter;

    /**
     * Create a new notification instance.
     */
    public function __construct(Room $room, User $inviter)
    {
        $this->room = $room;
        $this->inviter = $inviter;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pozivnica za sobu: ' . $this->room->name)
            ->greeting('Zdravo ' . $notifiable->name . '!')
            ->line($this->inviter->name . ' vas je pozvao da se pridružite sobi.')
            ->line('Soba: ' . $this->room->name)
            ->line('Opis: ' . $this->room->description)
            ->action('Pridruži se sobi', url('/rooms/' . $this->room->id . '/join'))
            ->line('Hvala što koristite našu aplikaciju!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'room_id' => $this->room->id,
            'room_name' => $this->room->name,
            'inviter_id' => $this->inviter->id,
            'inviter_name' => $this->inviter->name,
            'type' => 'room_invitation',
            'created_at' => now(),
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'room_id' => $this->room->id,
            'room_name' => $this->room->name,
            'inviter_id' => $this->inviter->id,
            'inviter_name' => $this->inviter->name,
            'type' => 'room_invitation',
            'created_at' => now(),
        ]);
    }
} 