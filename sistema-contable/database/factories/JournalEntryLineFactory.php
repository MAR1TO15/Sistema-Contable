<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalEntryLine>
 */
class JournalEntryLineFactory extends Factory
{
    /**
     * Define the model's default state: a debit on a postable account of
     * the entry's client.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'journal_entry_id' => JournalEntry::factory(),
            'account_id' => fn (array $attributes) => Account::factory()->create([
                'client_id' => JournalEntry::find($attributes['journal_entry_id'])->client_id,
            ])->id,
            'debit' => '100.00',
            'credit' => '0.00',
            'description' => null,
        ];
    }

    public function debit(string $amount): static
    {
        return $this->state(fn (array $attributes) => ['debit' => $amount, 'credit' => '0.00']);
    }

    public function credit(string $amount): static
    {
        return $this->state(fn (array $attributes) => ['debit' => '0.00', 'credit' => $amount]);
    }
}
