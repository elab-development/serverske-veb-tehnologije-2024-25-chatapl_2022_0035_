<?php

namespace App\Events;

use App\Models\User;
use App\Models\Room;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserLeftRoom implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $room;

    /**
     * Kreiranje nove instance događaja.
     */
    public function __construct(User $user, Room $room)
    {
        $this->user = $user;
        $this->room = $room;
    }

    /**
     * Dohvatanje kanala na kojima se događaj treba emitovati.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('room.' . $this->room->id),
        ];
    }

    /**
     * Dohvatanje podataka za emitovanje.
     */
    public function broadcastWith(): array
    {
        return [
            'user' => $this->user,
            'room' => $this->room,
            'type' => 'user_left_room'
        ];
    }
}
