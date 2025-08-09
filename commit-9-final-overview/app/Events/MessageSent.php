<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    /**
     * Kreiranje nove instance događaja.
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Dohvatanje kanala na kojima se događaj treba emitovati.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('room.' . $this->message->room_id),
        ];
    }

    /**
     * Dohvatanje podataka za emitovanje.
     */
    public function broadcastWith(): array
    {
        return [
            'message' => $this->message->load('user'),
            'type' => 'message_sent'
        ];
    }
}
