<?php

namespace Tests\Feature;

use App\Enums\ActivityType;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadBusinessRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_cannot_be_marked_won_without_an_activity(): void
    {
        $manager = User::factory()->manager()->create();
        $lead = Lead::factory()->create(['status' => 'new']);

        $response = $this->actingAs($manager, 'sanctum')
            ->patchJson("/api/leads/{$lead->id}", ['status' => 'won']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('status');

        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'status' => 'new']);
    }

    public function test_lead_can_be_marked_won_after_it_has_an_activity(): void
    {
        $manager = User::factory()->manager()->create();
        $lead = Lead::factory()->create(['status' => 'new']);
        $lead->activities()->create([
            'user_id' => $manager->id,
            'type' => ActivityType::Call,
            'body' => 'Closed the deal on a call.',
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($manager, 'sanctum')
            ->patchJson("/api/leads/{$lead->id}", ['status' => 'won']);

        $response->assertOk();
        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'status' => 'won']);
    }

    public function test_rep_can_log_an_activity_on_their_assigned_lead(): void
    {
        $rep = User::factory()->rep()->create();
        $lead = Lead::factory()->create(['assigned_to' => $rep->id]);

        $response = $this->actingAs($rep, 'sanctum')
            ->postJson("/api/leads/{$lead->id}/activities", [
                'type' => 'note',
                'body' => 'Left a voicemail.',
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('activities', ['lead_id' => $lead->id, 'user_id' => $rep->id]);
    }

    public function test_rep_cannot_log_an_activity_on_another_reps_lead(): void
    {
        $rep = User::factory()->rep()->create();
        $otherRep = User::factory()->rep()->create();
        $lead = Lead::factory()->create(['assigned_to' => $otherRep->id]);

        $response = $this->actingAs($rep, 'sanctum')
            ->postJson("/api/leads/{$lead->id}/activities", [
                'type' => 'note',
                'body' => 'Trying to log an activity on a lead that is not mine.',
            ]);

        $response->assertForbidden();
    }
}
