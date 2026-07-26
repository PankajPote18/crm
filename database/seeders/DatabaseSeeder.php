<?php

namespace Database\Seeders;

use App\Enums\LeadStatus;
use App\Models\Activity;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Login credentials (password for all): password
     * - manager@crm.test (manager)
     * - rep1@crm.test (rep)
     * - rep2@crm.test (rep)
     */
    public function run(): void
    {
        User::factory()->manager()->create([
            'name' => 'Alice Manager',
            'email' => 'manager@crm.test',
        ]);

        $reps = [
            User::factory()->rep()->create([
                'name' => 'Bob Rep',
                'email' => 'rep1@crm.test',
            ]),
            User::factory()->rep()->create([
                'name' => 'Carol Rep',
                'email' => 'rep2@crm.test',
            ]),
        ];

        foreach ($reps as $rep) {
            Lead::factory()
                ->count(10)
                ->create(['assigned_to' => $rep->id])
                ->each(function (Lead $lead) use ($rep) {
                    $isClosed = in_array($lead->status, [LeadStatus::Won, LeadStatus::Lost]);

                    if ($isClosed || fake()->boolean(60)) {
                        Activity::factory()
                            ->count(fake()->numberBetween(1, 3))
                            ->create([
                                'lead_id' => $lead->id,
                                'user_id' => $rep->id,
                            ]);
                    }
                });
        }

        Lead::factory()->count(5)->create([
            'assigned_to' => null,
            'status' => LeadStatus::New,
        ]);
    }
}
