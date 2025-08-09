<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * Atributi koji se mogu masovno dodeliti.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'password_reset_token',
        'password_reset_expires_at',
    ];

    /**
     * Atributi koji treba da budu sakriveni za serijalizaciju.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Dohvatanje atributa koji treba da budu kastovani.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Dohvatanje poruka za korisnika.
     */
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Dohvatanje soba za korisnika.
     */
    public function rooms()
    {
        return $this->belongsToMany(Room::class, 'user_room')
                    ->withPivot('role', 'is_online', 'last_seen_at')
                    ->withTimestamps();
    }
}
