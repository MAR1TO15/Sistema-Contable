<?php

namespace Database\Factories;

use App\Enums\AccountNature;
use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state: a postable asset account.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'parent_id' => null,
            'code' => fake()->unique()->numerify('9####'),
            'name' => fake()->unique()->words(3, true),
            'type' => AccountType::Activo,
            'nature' => AccountNature::Deudora,
            'is_postable' => true,
        ];
    }

    /**
     * Indicate that the account only groups other accounts.
     */
    public function group(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_postable' => false,
        ]);
    }

    /**
     * Place the account under the given parent, inheriting its client,
     * type and nature.
     */
    public function childOf(Account $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'client_id' => $parent->client_id,
            'parent_id' => $parent->id,
            'type' => $parent->type,
            'nature' => $parent->nature,
        ]);
    }
}
