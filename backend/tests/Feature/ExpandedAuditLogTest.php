<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpandedAuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_crud_is_written_to_system_logs(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin, 'sanctum');

        $divisionId = $this->postJson('/api/divisions', ['name' => 'Laboratory', 'code' => 'LAB'])
            ->assertCreated()->json('division.id');
        $sectionId = $this->postJson('/api/sections', ['division_id' => $divisionId, 'name' => 'Chemistry'])
            ->assertCreated()->json('section.id');

        $this->patchJson("/api/divisions/{$divisionId}", ['name' => 'Laboratory Services'])->assertOk();
        $this->patchJson("/api/sections/{$sectionId}", ['name' => 'Soil Chemistry'])->assertOk();
        $this->deleteJson("/api/sections/{$sectionId}")->assertOk();
        $this->deleteJson("/api/divisions/{$divisionId}")->assertOk();

        foreach (['CREATE', 'UPDATE', 'DELETE'] as $action) {
            $this->assertDatabaseHas('logs', ['user_id' => $admin->id, 'action' => $action]);
        }
        $this->assertDatabaseCount('logs', 6);
    }

    public function test_admin_profile_and_system_settings_updates_are_logged(): void
    {
        $admin = User::factory()->admin()->create();
        $originalPosition = $admin->position;
        $this->actingAs($admin, 'sanctum');

        $this->putJson('/api/admin/update-admin-info', ['position' => 'System Owner'])->assertOk();
        $this->putJson('/api/admin/settings', [
            'maintenance' => true,
            'email_notifs' => true,
            'auto_close' => true,
            'audit_log' => true,
            'sla_critical' => 2,
            'sla_high' => 8,
            'sla_medium' => 24,
            'sla_low' => 72,
        ])->assertOk();

        $this->assertDatabaseHas('logs', ['user_id' => $admin->id, 'message' => "Settings profile updated: position changed from '{$originalPosition}' to 'System Owner'."]);
        $this->assertDatabaseHas('logs', ['user_id' => $admin->id, 'message' => 'System settings updated: maintenance.']);
        $this->getJson('/api/admin/settings')->assertOk()->assertJsonPath('maintenance', true);
    }
}
