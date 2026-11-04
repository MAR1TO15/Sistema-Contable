<?php

namespace Database\Factories;

use App\Enums\PeriodStatus;
use App\Models\Client;
use App\Models\FiscalPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FiscalPeriod>
 */
class FiscalPeriodFactory extends Factory
{
    /**
     * Define the model's default state: an open calendar year.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = fake()->unique()->numberBetween(2000, 2099);

        return [
            'client_id' => Client::factory(),
            'name' => "Ejercicio {$year}",
            'start_date' => "{$year}-01-01",
            'end_date' => "{$year}-12-31",
            'status' => PeriodStatus::Abierto,
        ];
    }

    /**
     * Indicate that the period has been closed.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PeriodStatus::Cerrado,
        ]);
    }
}
