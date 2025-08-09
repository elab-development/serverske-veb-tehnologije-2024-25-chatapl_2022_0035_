<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'room_id',
        'content',
        'type',
        'file_path',
        'file_name',
        'file_size',
        'file_type',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    /**
     * Dohvatanje korisnika koji poseduje poruku.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Dohvatanje sobe koja poseduje poruku.
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
