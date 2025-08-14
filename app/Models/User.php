<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Get the messages for the user.
     */
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Get the rooms for the user.
     */
    public function rooms()
    {
        return $this->belongsToMany(Room::class, 'user_room')
                    ->withPivot('role', 'is_online', 'last_seen_at')
                    ->withTimestamps();
    }

    /**
     * Get the audit logs for the user.
     */
    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    /**
     * Check if user is moderator in a specific room.
     */
    public function isModeratorInRoom(int $roomId): bool
    {
        return $this->rooms()
                    ->where('room_id', $roomId)
                    ->whereIn('role', ['admin', 'moderator'])
                    ->exists();
    }

    /**
     * Check if user is admin in a specific room.
     */
    public function isAdminInRoom(int $roomId): bool
    {
        return $this->rooms()
                    ->where('room_id', $roomId)
                    ->where('role', 'admin')
                    ->exists();
    }

    /**
     * Get user's role in a specific room.
     */
    public function getRoleInRoom(int $roomId): ?string
    {
        $room = $this->rooms()->where('room_id', $roomId)->first();
        return $room ? $room->pivot->role : null;
    }

    /**
     * Check if user can moderate content.
     */
    public function canModerate(): bool
    {
        return $this->is_admin || $this->rooms()
                    ->whereIn('role', ['admin', 'moderator'])
                    ->exists();
    }

    /**
     * Check if user can delete messages.
     */
    public function canDeleteMessage(Message $message): bool
    {
        // Admin može da briše sve poruke
        if ($this->is_admin) {
            return true;
        }

        // Korisnik može da briše svoje poruke
        if ($this->id === $message->user_id) {
            return true;
        }

        // Moderator može da briše poruke u svojim sobama
        return $this->isModeratorInRoom($message->room_id);
    }

    /**
     * Check if user can edit messages.
     */
    public function canEditMessage(Message $message): bool
    {
        // Admin može da menja sve poruke
        if ($this->is_admin) {
            return true;
        }

        // Korisnik može da menja svoje poruke
        return $this->id === $message->user_id;
    }

    /**
     * Check if user can manage rooms.
     */
    public function canManageRooms(): bool
    {
        return $this->is_admin || $this->rooms()
                    ->where('role', 'admin')
                    ->exists();
    }
}
