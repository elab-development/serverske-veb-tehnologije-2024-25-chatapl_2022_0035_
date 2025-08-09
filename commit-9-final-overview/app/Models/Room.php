<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'max_users',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'max_users' => 'integer',
    ];

    /**
     * Dohvatanje korisnika za sobu.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_room')
                    ->withPivot('role', 'is_online', 'last_seen_at')
                    ->withTimestamps();
    }

    /**
     * Dohvatanje poruka za sobu.
     */
    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
