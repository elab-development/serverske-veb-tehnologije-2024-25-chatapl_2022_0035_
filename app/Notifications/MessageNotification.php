<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use App\Models\Message;
use App\Models\User;

class MessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $message;
    protected $sender;

    /**
     * Create a new notification instance.
     */
    public function __construct(Message $message, User $sender)
    {
        $this->message = $message;
        $this->sender = $sender;
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
            ->subject('Nova poruka u sobi: ' . $this->message->room->name)
            ->greeting('Zdravo ' . $notifiable->name . '!')
            ->line($this->sender->name . ' vam je poslao novu poruku.')
            ->line('Poruka: ' . $this->message->content)
            ->action('Pogledaj poruku', url('/rooms/' . $this->message->room_id))
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
            'message_id' => $this->message->id,
            'sender_id' => $this->sender->id,
            'sender_name' => $this->sender->name,
            'room_id' => $this->message->room_id,
            'room_name' => $this->message->room->name,
            'content' => $this->message->content,
            'type' => 'message',
            'created_at' => $this->message->created_at,
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'message_id' => $this->message->id,
            'sender_id' => $this->sender->id,
            'sender_name' => $this->sender->name,
            'room_id' => $this->message->room_id,
            'room_name' => $this->message->room->name,
            'content' => $this->message->content,
            'type' => 'message',
            'created_at' => $this->message->created_at,
        ]);
    }
} 