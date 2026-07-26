<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_returns_correct_counts_and_values_for_a_rep(): void
    {
        $rep = User::factory()->rep()->create();

        Lead::factory()->create(['assigned_to' => $rep->id, 'status' => 'won', 'expected_value' => 1000]);
        Lead::factory()->create(['assigned_to' => $rep->id, 'status' => 'won', 'expected_value' => 500]);
        Lead::factory()->create(['assigned_to' => $rep->id, 'status' => 'new', 'expected_value' => 2000]);

        $leads = Lead::where('assigned_to', $rep->id)->get();
        Activity::factory()->create(['lead_id' => $leads[0]->id, 'user_id' => $rep->id]);
        Activity::factory()->create(['lead_id' => $leads[1]->id, 'user_id' => $rep->id]);

        $response = $this->actingAs($rep, 'sanctum')->getJson('/api/reports/rep-performance');

        $response->assertOk();
        $report = $response->json('data.0');

        $this->assertSame($rep->id, $report['rep_id']);
        $this->assertSame(3, $report['total_leads']);
        $this->assertSame(2, $report['leads_by_status']['won']);
        $this->assertSame(1, $report['leads_by_status']['new']);
        $this->assertEquals(3500, $report['total_expected_value']);
        $this->assertEquals(1500, $report['won_expected_value']);
        $this->assertSame(2, $report['activity_count']);
    }

    public function test_rep_only_sees_their_own_report(): void
    {
        $rep = User::factory()->rep()->create();
        $otherRep = User::factory()->rep()->create();

        Lead::factory()->create(['assigned_to' => $rep->id]);
        Lead::factory()->create(['assigned_to' => $otherRep->id]);

        $response = $this->actingAs($rep, 'sanctum')->getJson('/api/reports/rep-performance');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_manager_sees_every_reps_report(): void
    {
        $manager = User::factory()->manager()->create();
        $repOne = User::factory()->rep()->create();
        $repTwo = User::factory()->rep()->create();

        Lead::factory()->create(['assigned_to' => $repOne->id]);
        Lead::factory()->create(['assigned_to' => $repTwo->id]);

        $response = $this->actingAs($manager, 'sanctum')->getJson('/api/reports/rep-performance');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_report_query_count_does_not_grow_with_the_number_of_leads(): void
    {
        $manager = User::factory()->manager()->create();
        $reps = User::factory()->count(5)->rep()->create();

        foreach ($reps as $rep) {
            $leads = Lead::factory()->count(50)->create(['assigned_to' => $rep->id]);
            Activity::factory()->create(['lead_id' => $leads->first()->id, 'user_id' => $rep->id]);
        }

        DB::enableQueryLog();
        $this->actingAs($manager, 'sanctum')->getJson('/api/reports/rep-performance')->assertOk();
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        // The report is built from grouped aggregate queries, so the query count
        // must stay fixed no matter how many reps or leads exist (250 leads here).
        $this->assertLessThanOrEqual(6, $queryCount);
    }
}
