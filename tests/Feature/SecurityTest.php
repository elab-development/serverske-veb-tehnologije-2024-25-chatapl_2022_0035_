<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_rate_limiting_on_login()
    {
        // Try to login multiple times quickly
        for ($i = 0; $i < 4; $i++) {
            $response = $this->postJson('/api/login', [
                'email' => 'test@example.com',
                'password' => 'password123'
            ]);
            
            if ($i < 3) {
                $response->assertStatus(422); // Validation error
            }
        }
        
        // 4th attempt should be rate limited
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        
        $response->assertStatus(429)
            ->assertJson([
                'success' => false,
                'error_code' => 'RATE_LIMIT_EXCEEDED'
            ]);
    }

    public function test_rate_limiting_on_register()
    {
        // Try to register multiple times quickly
        for ($i = 0; $i < 3; $i++) {
            $response = $this->postJson('/api/register', [
                'name' => 'Test User',
                'email' => "test{$i}@example.com",
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!'
            ]);
            
            if ($i < 2) {
                $response->assertStatus(201); // Success
            }
        }
        
        // 3rd attempt should be rate limited
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test3@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!'
        ]);
        
        $response->assertStatus(429)
            ->assertJson([
                'success' => false,
                'error_code' => 'RATE_LIMIT_EXCEEDED'
            ]);
    }

    public function test_xss_protection_middleware()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->postJson('/api/messages', [
                'room_id' => 1,
                'content' => '<script>alert("XSS")</script>Hello World'
            ]);
        
        // Should sanitize the content
        $response->assertStatus(422); // Validation error for room_id
        
        // Test with valid room
        $room = \App\Models\Room::factory()->create();
        $room->users()->attach($user->id, ['role' => 'member']);
        
        $response = $this->actingAs($user)
            ->postJson('/api/messages', [
                'room_id' => $room->id,
                'content' => '<script>alert("XSS")</script>Hello World'
            ]);
        
        $response->assertStatus(201);
        
        // Check that the content was sanitized
        $this->assertDatabaseHas('messages', [
            'room_id' => $room->id,
            'user_id' => $user->id,
            'content' => '&lt;script&gt;alert("XSS")&lt;/script&gt;Hello World'
        ]);
    }

    public function test_input_validation_middleware()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'A', // Too short
            'email' => 'invalid-email', // Invalid email
            'password' => '123', // Too short and weak
            'password_confirmation' => '123'
        ]);
        
        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error_code' => 'VALIDATION_ERROR'
            ])
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_password_strength_validation()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'weakpassword', // Missing uppercase, number, special char
            'password_confirmation' => 'weakpassword'
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_file_upload_validation()
    {
        $user = User::factory()->create();
        $room = \App\Models\Room::factory()->create();
        $room->users()->attach($user->id, ['role' => 'member']);
        
        // Test with invalid file type
        $response = $this->actingAs($user)
            ->postJson('/api/messages/upload', [
                'room_id' => $room->id,
                'file' => 'invalid-file.exe' // Executable file not allowed
            ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_rate_limit_headers()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        
        $response->assertHeader('X-RateLimit-Limit')
            ->assertHeader('X-RateLimit-Remaining')
            ->assertHeader('X-RateLimit-Reset');
    }

    public function test_xss_protection_headers()
    {
        $response = $this->get('/api/rooms');
        
        $response->assertHeader('X-XSS-Protection', '1; mode=block')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_sql_injection_protection()
    {
        $user = User::factory()->create();
        $room = \App\Models\Room::factory()->create();
        $room->users()->attach($user->id, ['role' => 'member']);
        
        // Test with SQL injection attempt
        $response = $this->actingAs($user)
            ->postJson('/api/messages', [
                'room_id' => $room->id,
                'content' => "'; DROP TABLE users; --"
            ]);
        
        // Should not cause SQL error, should be sanitized
        $response->assertStatus(201);
        
        // Check that the content was stored safely
        $this->assertDatabaseHas('messages', [
            'room_id' => $room->id,
            'user_id' => $user->id,
            'content' => "'; DROP TABLE users; --"
        ]);
    }

    public function test_csrf_protection()
    {
        // CSRF protection should be active for non-GET requests
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!'
        ]);
        
        // Should not fail due to CSRF (API routes are exempt)
        $response->assertStatus(201);
    }
} 