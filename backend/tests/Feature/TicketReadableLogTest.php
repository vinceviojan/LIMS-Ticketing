<?php

namespace Tests\Feature;

use App\Models\ProblemCategory;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketReadableLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_relationship_updates_use_names_in_system_logs(): void
    {
        $admin = User::factory()->admin()->create();
        $requester = User::factory()->user()->create();
        $staff = User::factory()->staff()->create([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'name' => 'Juan Dela Cruz',
        ]);
        $category = ProblemCategory::create([
            'type' => 'Hardware',
            'categories' => 'Printer Issue',
        ]);
        $ticket = Ticket::create([
            'user_id' => $requester->id,
            'ticket_no' => 'STN-2026-0003',
            'issue' => 'Printer unavailable',
        ]);

        $this->actingAs($admin, 'sanctum')->putJson("/api/tickets/{$ticket->id}", [
            'assigned_staff_id' => $staff->id,
            'problem_category_id' => $category->id,
        ])->assertOk();

        $this->assertDatabaseHas('logs', [
            'ticket_id' => $ticket->id,
            'action' => 'UPDATE',
            'message' => 'Ticket STN-2026-0003 updated: assigned staff changed to Juan Dela Cruz, problem category changed to Hardware / Printer Issue.',
        ]);
    }

    public function test_unassigned_relationships_have_readable_labels(): void
    {
        $admin = User::factory()->admin()->create();
        $requester = User::factory()->user()->create();
        $staff = User::factory()->staff()->create();
        $category = ProblemCategory::create(['type' => 'Network', 'categories' => 'Wi-Fi']);
        $ticket = Ticket::create([
            'user_id' => $requester->id,
            'assigned_staff_id' => $staff->id,
            'problem_category_id' => $category->id,
            'ticket_no' => 'STN-2026-0004',
            'issue' => 'Connection issue',
        ]);

        $this->actingAs($admin, 'sanctum')->putJson("/api/tickets/{$ticket->id}", [
            'assigned_staff_id' => null,
            'problem_category_id' => null,
        ])->assertOk();

        $this->assertDatabaseHas('logs', [
            'ticket_id' => $ticket->id,
            'message' => 'Ticket STN-2026-0004 updated: assigned staff changed to Unassigned, problem category changed to No category.',
        ]);
    }
}
