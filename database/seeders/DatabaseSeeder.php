<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run seeders in order
        $this->call([
            UserSeeder::class,
            RoomSeeder::class,
        ]);

        // Create some sample messages
        \App\Models\Message::factory(50)->create();
    }
}
