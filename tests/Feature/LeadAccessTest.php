<?php

namespace Tests\Feature;

use App\Enums\ActivityType;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LeadAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_rep_only_sees_their_own_leads_in_the_list(): void
    {
        $rep = User::factory()->rep()->create();
        $otherRep = User::factory()->rep()->create();

        Lead::factory()->count(3)->create(['assigned_to' => $rep->id]);
        Lead::factory()->count(2)->create(['assigned_to' => $otherRep->id]);

        $response = $this->actingAs($rep, 'sanctum')->getJson('/api/leads');

        $response->assertOk()
            ->assertJsonPath('data.pagination.total', 3);
    }

    public function test_per_page_is_capped_to_avoid_returning_the_entire_table(): void
    {
        $manager = User::factory()->manager()->create();
        Lead::factory()->count(5)->create();

        $response = $this->actingAs($manager, 'sanctum')->getJson('/api/leads?per_page=99999');

        $response->assertOk()
            ->assertJsonPath('data.pagination.per_page', 100);
    }

    public function test_rep_cannot_view_another_reps_lead(): void
    {
        $rep = User::factory()->rep()->create();
        $otherRep = User::factory()->rep()->create();

        $lead = Lead::factory()->create(['assigned_to' => $otherRep->id]);

        $response = $this->actingAs($rep, 'sanctum')->getJson("/api/leads/{$lead->id}");

        $response->assertForbidden();
    }

    public function test_manager_sees_all_leads_in_the_list(): void
    {
        $manager = User::factory()->manager()->create();
        $rep = User::factory()->rep()->create();
        $otherRep = User::factory()->rep()->create();

        Lead::factory()->count(3)->create(['assigned_to' => $rep->id]);
        Lead::factory()->count(2)->create(['assigned_to' => $otherRep->id]);

        $response = $this->actingAs($manager, 'sanctum')->getJson('/api/leads');

        $response->assertOk()
            ->assertJsonPath('data.pagination.total', 5);
    }

    public function test_manager_can_view_any_reps_lead(): void
    {
        $manager = User::factory()->manager()->create();
        $rep = User::factory()->rep()->create();
        $lead = Lead::factory()->create(['assigned_to' => $rep->id]);

        $response = $this->actingAs($manager, 'sanctum')->getJson("/api/leads/{$lead->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $lead->id);
    }

    public function test_only_manager_can_assign_a_lead(): void
    {
        $rep = User::factory()->rep()->create();
        $anotherRep = User::factory()->rep()->create();
        $lead = Lead::factory()->create(['assigned_to' => $rep->id]);

        $response = $this->actingAs($rep, 'sanctum')->postJson("/api/leads/{$lead->id}/assign", [
            'assigned_to' => $anotherRep->id,
        ]);

        $response->assertForbidden();
    }

    public function test_manager_can_assign_a_lead_to_a_valid_rep(): void
    {
        $manager = User::factory()->manager()->create();
        $rep = User::factory()->rep()->create();
        $lead = Lead::factory()->create(['assigned_to' => null]);

        $response = $this->actingAs($manager, 'sanctum')->postJson("/api/leads/{$lead->id}/assign", [
            'assigned_to' => $rep->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.assigned_rep.id', $rep->id);

        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'assigned_to' => $rep->id]);
    }

    public function test_lead_cannot_be_assigned_to_a_user_who_is_not_a_rep(): void
    {
        $manager = User::factory()->manager()->create();
        $anotherManager = User::factory()->manager()->create();
        $lead = Lead::factory()->create(['assigned_to' => null]);

        $response = $this->actingAs($manager, 'sanctum')->postJson("/api/leads/{$lead->id}/assign", [
            'assigned_to' => $anotherManager->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('assigned_to');

        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'assigned_to' => null]);
    }

    public function test_rep_cannot_reassign_their_own_lead_through_the_update_endpoint(): void
    {
        $rep = User::factory()->rep()->create();
        $otherRep = User::factory()->rep()->create();
        $lead = Lead::factory()->create(['assigned_to' => $rep->id]);

        $response = $this->actingAs($rep, 'sanctum')->patchJson("/api/leads/{$lead->id}", [
            'assigned_to' => $otherRep->id,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'assigned_to' => $rep->id]);
    }

    public function test_creating_a_lead_ignores_an_assigned_to_field(): void
    {
        $manager = User::factory()->manager()->create();
        $rep = User::factory()->rep()->create();

        $response = $this->actingAs($manager, 'sanctum')->postJson('/api/leads', [
            'name' => 'New Lead',
            'email' => 'newlead@test.com',
            'phone' => '1234567890',
            'source' => 'web',
            'expected_value' => 100,
            'assigned_to' => $rep->id,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('leads', ['id' => $response->json('data.id'), 'assigned_to' => null]);
    }

    public function test_viewing_a_lead_uses_a_fixed_number_of_queries_regardless_of_activity_count(): void
    {
        $manager = User::factory()->manager()->create();
        $rep = User::factory()->rep()->create();
        $lead = Lead::factory()->create(['assigned_to' => $rep->id]);

        for ($i = 0; $i < 10; $i++) {
            $lead->activities()->create([
                'user_id' => $rep->id,
                'type' => ActivityType::Note,
                'body' => "Activity {$i}",
                'occurred_at' => now(),
            ]);
        }

        DB::enableQueryLog();
        $this->actingAs($manager, 'sanctum')->getJson("/api/leads/{$lead->id}")->assertOk();
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Loading the lead, its rep, and 10 activities (with each activity's user) should
        // stay at a handful of queries — it must not grow with the number of activities.
        $this->assertLessThanOrEqual(6, $queryCount);
    }
}
