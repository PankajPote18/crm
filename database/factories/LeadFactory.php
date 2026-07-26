<?php

namespace Database\Factories;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    /**
     * Sensible Indian Rupee deal sizes, from small orders to large B2B contracts.
     */
    private const DEAL_SIZES = [
        25000, 35000, 50000, 75000, 100000, 150000, 200000, 250000,
        350000, 500000, 750000, 850000, 1000000, 1500000, 2000000, 2500000, 5000000,
    ];

    private const COMPANY_WORDS = [
        'Textiles', 'InfoTech', 'Agro Exports', 'Constructions', 'Pharma', 'Software Solutions',
        'Logistics', 'Exports', 'Traders', 'Industries', 'Foods', 'Retail',
        'Motors', 'Realty', 'Consulting', 'Apparels', 'Chemicals', 'Electronics', 'Energy',
    ];

    private const COMPANY_SUFFIXES = ['Pvt Ltd', 'Ltd', '& Sons', 'Group', 'Enterprises', 'Corporation'];

    private const EMAIL_DOMAINS = ['gmail.com', 'yahoo.in', 'outlook.com', 'rediffmail.com', 'hotmail.com'];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->name();

        return [
            'name' => $name,
            'email' => $this->emailFor($name),
            'phone' => $this->indianMobileNumber(),
            'company' => fake()->optional(0.75)->passthrough($this->companyName()),
            'source' => fake()->randomElement(LeadSource::cases()),
            'status' => LeadStatus::New,
            'expected_value' => fake()->randomElement(self::DEAL_SIZES),
            'assigned_to' => null,
        ];
    }

    private function emailFor(string $name): string
    {
        $domain = fake()->randomElement(self::EMAIL_DOMAINS);

        return Str::slug($name, '.').fake()->unique()->numberBetween(1, 999).'@'.$domain;
    }

    private function indianMobileNumber(): string
    {
        return fake()->randomElement(['6', '7', '8', '9']).fake()->numerify('#########');
    }

    private function companyName(): string
    {
        return sprintf(
            '%s %s %s',
            fake()->lastName(),
            fake()->randomElement(self::COMPANY_WORDS),
            fake()->randomElement(self::COMPANY_SUFFIXES)
        );
    }
}
