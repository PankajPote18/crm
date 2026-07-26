<?php

namespace Database\Seeders;

use App\Enums\LeadStatus;
use App\Models\Activity;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    private const REP_NAMES = [
        'Priya Nair',
        'Arjun Verma',
        'Ananya Iyer',
        'Karan Malhotra',
        'Sneha Reddy',
        'Vikram Singh',
    ];

    private const LEADS_PER_REP = 25;

    private const UNASSIGNED_LEADS = 30;

    /**
     * Seed the application's database.
     *
     * Every run replaces existing leads, activities, and users with a fresh dataset.
     *
     * Login credentials (password for all): password
     * - manager@crm.test (manager)
     * - one rep account per name in REP_NAMES, e.g. priya.nair@crm.test (rep)
     */
    public function run(): void
    {
        Activity::query()->delete();
        Lead::query()->delete();
        User::query()->delete();

        User::factory()->manager()->create([
            'name' => 'Rohan Mehta',
            'email' => 'manager@crm.test',
        ]);

        $reps = collect(self::REP_NAMES)->map(fn (string $name) => User::factory()->rep()->create([
            'name' => $name,
            'email' => Str::slug($name, '.').'@crm.test',
        ]));

        foreach ($reps as $rep) {
            Lead::factory()
                ->count(self::LEADS_PER_REP)
                ->state(fn () => ['status' => self::randomStatus()])
                ->create(['assigned_to' => $rep->id])
                ->each(fn (Lead $lead) => $this->seedActivities($lead, $rep));
        }

        Lead::factory()->count(self::UNASSIGNED_LEADS)->create([
            'assigned_to' => null,
            'status' => LeadStatus::New,
        ]);
    }

    /**
     * Weighted so most leads sit mid-pipeline, with fewer brand new or closed ones.
     */
    private static function randomStatus(): LeadStatus
    {
        return match (true) {
            ($n = fake()->numberBetween(1, 100)) <= 15 => LeadStatus::New,
            $n <= 40 => LeadStatus::Contacted,
            $n <= 65 => LeadStatus::Qualified,
            $n <= 85 => LeadStatus::Won,
            default => LeadStatus::Lost,
        };
    }

    private function seedActivities(Lead $lead, User $rep): void
    {
        $occurredAt = now()->subDays(fake()->numberBetween(20, 90));

        foreach ($this->timelineFor($lead->status) as $state) {
            $occurredAt = $occurredAt->addDays(fake()->numberBetween(1, 6));

            if ($occurredAt->isFuture()) {
                $occurredAt = now();
            }

            Activity::factory()->{$state}()->create([
                'lead_id' => $lead->id,
                'user_id' => $rep->id,
                'occurred_at' => $occurredAt,
            ]);
        }
    }

    /**
     * The sequence of activity states that realistically lead to each status,
     * e.g. a won lead should show a full call -> demo -> quote -> negotiation -> close trail.
     *
     * @return list<string>
     */
    private function timelineFor(LeadStatus $status): array
    {
        return match ($status) {
            LeadStatus::New => fake()->boolean(30) ? ['call'] : [],
            LeadStatus::Contacted => fake()->boolean(50) ? ['call', 'email'] : ['call'],
            LeadStatus::Qualified => array_values(array_filter([
                'call',
                'meeting',
                fake()->boolean(60) ? 'email' : null,
                fake()->boolean(40) ? 'note' : null,
            ])),
            LeadStatus::Won => array_values(array_filter([
                'call',
                'meeting',
                'email',
                'meeting',
                fake()->boolean(50) ? 'email' : null,
                'wonNote',
            ])),
            LeadStatus::Lost => array_values(array_filter([
                'call',
                'meeting',
                fake()->boolean(50) ? 'email' : null,
                'lostNote',
            ])),
        };
    }
}
