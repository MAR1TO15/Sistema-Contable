<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Firm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'firm_id' => Firm::factory(),
            'name' => fake()->unique()->company(),
            'tax_id' => fake()->unique()->numerify('#########'),
            'is_active' => true,
        ];
    }
}
