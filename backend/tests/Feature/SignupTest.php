<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignupTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_register(): void
    {
        $response = $this->postJson('/api/register', [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'division' => 'Laboratory Services Division',
            'sections' => 'Soil Chemistry',
            'position' => 'Analyst',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Registration successful.')
            ->assertJsonPath('user.email', 'juan@example.com')
            ->assertJsonMissingPath('user.password');

        $this->assertDatabaseHas('users', [
            'email' => 'juan@example.com',
            'role' => 'USER',
            'status' => 'ACTIVE',
        ]);
        $userId = $response->json('user.id');
        $this->assertDatabaseHas('logs', [
            'user_id' => $userId,
            'action' => 'CREATE',
            'message' => "User #{$userId} registered: Juan Dela Cruz (juan@example.com), role USER.",
            'address' => '127.0.0.1',
        ]);
    }

    public function test_registration_requires_matching_password_confirmation(): void
    {
        $response = $this->postJson('/api/register', [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different-password',
            'division' => 'Laboratory Services Division',
            'sections' => 'Soil Chemistry',
            'position' => 'Analyst',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }
}
