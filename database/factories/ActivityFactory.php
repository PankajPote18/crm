<?php

namespace Database\Factories;

use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    private const CALL_BODIES = [
        'Called the customer to introduce our product and understand their requirements.',
        "Follow-up call to check the customer's interest and decision timeline.",
        "Called to schedule a product demo at the customer's convenience.",
        'Reminder call regarding the quotation shared earlier.',
        'Called to discuss pricing options and answer product-related questions.',
        'Follow-up call regarding the pending payment for the confirmed order.',
        "Called to check on the status of internal approvals on the customer's side.",
    ];

    private const EMAIL_BODIES = [
        'Sent the company profile and product brochure over email.',
        "Emailed a detailed quotation based on the customer's requirements.",
        'Sent a follow-up email requesting feedback on the shared proposal.',
        'Emailed the revised quotation after adjusting pricing as discussed.',
        'Sent a payment reminder email along with the invoice copy.',
        'Shared onboarding and setup documents via email after order confirmation.',
        'Emailed the requested GST and company registration documents.',
    ];

    private const MEETING_BODIES = [
        'Conducted a discovery meeting to understand business requirements in detail.',
        "Held a product demo session for the customer's team.",
        'Met in person to discuss pricing and negotiate contract terms.',
        'Final negotiation meeting to finalize discount and payment terms.',
        'Kickoff meeting held after successful deal closure.',
        'Met with the customer to review the proposal and address concerns.',
    ];

    private const NOTE_BODIES = [
        'Customer requested GST details and company registration documents before proceeding.',
        "Noted budget constraints on the customer's side; may revisit next quarter.",
        'Customer requested more time to finalize internal approval.',
    ];

    private const WON_NOTE_BODIES = [
        'Deal closed successfully — customer confirmed the purchase order.',
        'Customer signed off on the order after final negotiation.',
        'Purchase order received; deal marked as won.',
    ];

    private const LOST_NOTE_BODIES = [
        'Lead marked as lost — customer decided to go with a competitor.',
        "Lead marked as lost due to budget constraints on the customer's side.",
        'Customer went unresponsive after multiple follow-ups; marking as lost.',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(ActivityType::cases());

        return [
            'lead_id' => Lead::factory(),
            'user_id' => User::factory(),
            'type' => $type,
            'body' => fake()->randomElement($this->bodiesFor($type)),
            'occurred_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    public function call(): static
    {
        return $this->state(fn () => [
            'type' => ActivityType::Call,
            'body' => fake()->randomElement(self::CALL_BODIES),
        ]);
    }

    public function email(): static
    {
        return $this->state(fn () => [
            'type' => ActivityType::Email,
            'body' => fake()->randomElement(self::EMAIL_BODIES),
        ]);
    }

    public function meeting(): static
    {
        return $this->state(fn () => [
            'type' => ActivityType::Meeting,
            'body' => fake()->randomElement(self::MEETING_BODIES),
        ]);
    }

    public function note(): static
    {
        return $this->state(fn () => [
            'type' => ActivityType::Note,
            'body' => fake()->randomElement(self::NOTE_BODIES),
        ]);
    }

    public function wonNote(): static
    {
        return $this->state(fn () => [
            'type' => ActivityType::Note,
            'body' => fake()->randomElement(self::WON_NOTE_BODIES),
        ]);
    }

    public function lostNote(): static
    {
        return $this->state(fn () => [
            'type' => ActivityType::Note,
            'body' => fake()->randomElement(self::LOST_NOTE_BODIES),
        ]);
    }

    /**
     * @return list<string>
     */
    private function bodiesFor(ActivityType $type): array
    {
        return match ($type) {
            ActivityType::Call => self::CALL_BODIES,
            ActivityType::Email => self::EMAIL_BODIES,
            ActivityType::Meeting => self::MEETING_BODIES,
            ActivityType::Note => self::NOTE_BODIES,
        };
    }
}
