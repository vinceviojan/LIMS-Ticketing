<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_get_chart_counts_from_the_database(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->staff()->create();
        $requester = User::factory()->user()->create();

        Ticket::create([
            'user_id' => $requester->id,
            'ticket_no' => 'TEST-001',
            'status' => 'OPEN',
            'urgency' => 'HIGH',
        ]);
        Ticket::create([
            'user_id' => $requester->id,
            'ticket_no' => 'TEST-002',
            'status' => 'CLOSE',
            'urgency' => 'LOW',
        ]);

        $this->actingAs($admin, 'sanctum')->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('user_roles.ADMIN', 1)
            ->assertJsonPath('user_roles.STAFF', 1)
            ->assertJsonPath('user_roles.USER', 1)
            ->assertJsonPath('tickets_by_status.OPEN', 1)
            ->assertJsonPath('tickets_by_status.CLOSE', 1)
            ->assertJsonPath('tickets_by_status.RESOLVED', 0)
            ->assertJsonPath('tickets_by_priority.HIGH', 1)
            ->assertJsonPath('tickets_by_priority.LOW', 1)
            ->assertJsonPath('tickets_by_priority.CRITICAL', 0);
    }

    public function test_non_admin_cannot_get_admin_chart_counts(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff, 'sanctum')->getJson('/api/admin/dashboard')->assertForbidden();
    }
}
