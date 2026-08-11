<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSettingsLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_and_password_settings_are_saved_and_logged(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'old-password']);
        $this->actingAs($admin, 'sanctum');

        $this->patchJson("/api/users/{$admin->id}", ['position' => 'Director'])->assertOk();
        $this->assertDatabaseHas('logs', ['user_id' => $admin->id, 'action' => 'UPDATE']);

        $this->patchJson("/api/users/{$admin->id}", [
            'current_password' => 'wrong-password',
            'password' => 'new-password123',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');

        $this->patchJson("/api/users/{$admin->id}", [
            'current_password' => 'old-password',
            'password' => 'new-password123',
        ])->assertOk();

        $this->assertDatabaseHas('logs', [
            'user_id' => $admin->id,
            'action' => 'UPDATE',
            'message' => "User #{$admin->id} ({$admin->email}) updated: password changed.",
        ]);
        $this->assertDatabaseMissing('logs', ['message' => 'new-password123']);
    }
}
