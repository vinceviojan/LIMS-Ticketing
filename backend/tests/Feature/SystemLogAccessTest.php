<?php

namespace Tests\Feature;

use App\Models\Log;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemLogAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_actor_roles_and_can_filter_system_logs(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->staff()->create();
        $user = User::factory()->user()->create();

        foreach ([$admin, $staff, $user] as $actor) {
            Log::create(['user_id' => $actor->id, 'action' => 'CREATE', 'message' => "{$actor->role} action"]);
        }

        $this->actingAs($admin, 'sanctum')->getJson('/api/admin/logs?role=STAFF')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.user.id', $staff->id)
            ->assertJsonPath('0.user.role', 'STAFF');
    }

    public function test_staff_and_user_cannot_open_admin_system_logs(): void
    {
        foreach ([User::factory()->staff()->create(), User::factory()->user()->create()] as $actor) {
            $this->actingAs($actor, 'sanctum')->getJson('/api/admin/logs')->assertForbidden();
        }
    }
}
