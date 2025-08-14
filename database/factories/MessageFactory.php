<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'room_id' => Room::factory(),
            'content' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement(['text', 'image', 'file']),
            'file_path' => $this->faker->optional()->filePath(),
            'is_read' => $this->faker->boolean(),
        ];
    }
}
