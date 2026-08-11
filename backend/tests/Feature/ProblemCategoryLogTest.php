<?php

namespace Tests\Feature;

use App\Models\ProblemCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProblemCategoryLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_problem_category_mutations_are_written_to_system_logs(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin, 'sanctum');

        $categoryId = $this->postJson('/api/problem-categories', [
            'type' => 'Hardware',
            'categories' => 'Printer Issue',
        ])->assertCreated()->json('data.id');

        $this->assertDatabaseHas('logs', [
            'user_id' => $admin->id,
            'action' => 'CREATE',
            'message' => "Problem category #{$categoryId} created: Hardware / Printer Issue.",
            'address' => '127.0.0.1',
        ]);

        $this->patchJson("/api/problem-categories/{$categoryId}", [
            'categories' => 'Network Printer Issue',
        ])->assertOk();

        $this->assertDatabaseHas('logs', [
            'user_id' => $admin->id,
            'action' => 'UPDATE',
            'message' => "Problem category #{$categoryId} updated: categories changed from 'Printer Issue' to 'Network Printer Issue'.",
        ]);

        $this->deleteJson("/api/problem-categories/{$categoryId}")->assertOk();

        $this->assertDatabaseMissing('problem_categories', ['id' => $categoryId]);
        $this->assertDatabaseHas('logs', [
            'user_id' => $admin->id,
            'action' => 'DELETE',
            'message' => "Problem category #{$categoryId} deleted: Hardware / Network Printer Issue.",
        ]);
    }

    public function test_unchanged_update_does_not_create_a_misleading_log(): void
    {
        $admin = User::factory()->admin()->create();
        $category = ProblemCategory::create(['type' => 'Software', 'categories' => 'LIMS']);

        $this->actingAs($admin, 'sanctum')->patchJson("/api/problem-categories/{$category->id}", [
            'type' => 'Software',
            'categories' => 'LIMS',
        ])->assertOk();

        $this->assertDatabaseCount('logs', 0);
    }
}
