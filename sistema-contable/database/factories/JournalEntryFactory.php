<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalEntry>
 */
class JournalEntryFactory extends Factory
{
    /**
     * Define the model's default state: an entry dated on the first day of
     * an open period of the same client.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'fiscal_period_id' => fn (array $attributes) => FiscalPeriod::factory()->create([
                'client_id' => $attributes['client_id'],
            ])->id,
            'user_id' => User::factory(),
            'number' => fake()->unique()->numberBetween(1, 1_000_000),
            'date' => fn (array $attributes) => FiscalPeriod::find($attributes['fiscal_period_id'])->start_date,
            'description' => fake()->sentence(4),
        ];
    }
}
