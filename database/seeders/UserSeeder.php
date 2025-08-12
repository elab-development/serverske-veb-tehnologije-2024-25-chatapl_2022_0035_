<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test users
        User::create([
            'name' => 'Masa Stevanovic',
            'email' => 'masa@example.com',
            'password' => Hash::make('password123'),
        ]);

        User::create([
            'name' => 'Luka Simic',
            'email' => 'luka@example.com',
            'password' => Hash::make('password123'),
        ]);

        User::create([
            'name' => 'Andrej Djordjevic',
            'email' => 'andrej@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Create additional users using factory
        User::factory(10)->create();
    }
}
