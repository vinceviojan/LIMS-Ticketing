<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SectionDivisionFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_sections_can_be_filtered_by_their_division(): void
    {
        $admin = User::factory()->admin()->create();
        $laboratory = Division::create(['name' => 'Laboratory']);
        $administration = Division::create(['name' => 'Administration']);
        $chemistry = Section::create(['division_id' => $laboratory->id, 'name' => 'Chemistry']);
        Section::create(['division_id' => $administration->id, 'name' => 'Human Resources']);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/sections?division_id={$laboratory->id}")
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $chemistry->id)
            ->assertJsonPath('0.division_id', $laboratory->id);
    }

    public function test_invalid_division_filter_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/sections?division_id=999999')
            ->assertUnprocessable();
    }
}
