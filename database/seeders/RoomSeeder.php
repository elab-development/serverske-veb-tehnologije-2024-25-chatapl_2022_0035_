<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test rooms
        $rooms = [
            [
                'name' => 'General Chat',
                'description' => 'General discussion room for all users',
                'type' => 'public',
                'max_users' => 50,
            ],
            [
                'name' => 'Tech Talk',
                'description' => 'Discussion about technology and programming',
                'type' => 'public',
                'max_users' => 30,
            ],
            [
                'name' => 'Gaming Room',
                'description' => 'Gaming discussions and strategies',
                'type' => 'public',
                'max_users' => 25,
            ],
            [
                'name' => 'Private Team Room',
                'description' => 'Private room for team discussions',
                'type' => 'private',
                'max_users' => 10,
            ],
        ];

        foreach ($rooms as $roomData) {
            $room = Room::create($roomData);
            
            // Add first user as admin
            $firstUser = User::first();
            if ($firstUser) {
                $room->users()->attach($firstUser->id, [
                    'role' => 'admin',
                    'is_online' => false,
                    'last_seen_at' => now(),
                ]);
            }
        }
    }
}
