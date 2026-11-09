<?php

namespace App\Http\Requests\JournalEntries;

use App\Enums\PeriodStatus;
use App\Models\Client;
use App\Models\FiscalPeriod;
use App\Services\ActiveClient;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreJournalEntryRequest extends FormRequest
{
    /**
     * The open period that contains the entry date, found during validation.
     */
    private ?FiscalPeriod $fiscalPeriod = null;

    /**
     * Access is already guaranteed by the active.client middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Drop the blank rows the dynamic form may send.
     */
    protected function prepareForValidation(): void
    {
        if (! is_array($this->input('lines'))) {
            return;
        }

        $lines = array_filter($this->input('lines'), function ($line) {
            return is_array($line) && collect($line)
                ->only(['account_id', 'debit', 'credit', 'description'])
                ->contains(fn ($value) => trim((string) $value) !== '');
        });

        $this->merge(['lines' => array_values($lines)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'description' => ['required', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => [
                'required',
                'integer',
                Rule::exists('accounts', 'id')
                    ->where('client_id', $this->activeClient()->id)
                    ->where('is_postable', 1),
            ],
            'lines.*.debit' => ['nullable', 'regex:'.Money::PATTERN],
            'lines.*.credit' => ['nullable', 'regex:'.Money::PATTERN],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lines.required' => 'El asiento debe tener líneas.',
            'lines.min' => 'El asiento debe tener al menos dos líneas.',
            'lines.*.account_id.required' => 'Cada línea debe indicar una cuenta.',
            'lines.*.account_id.exists' => 'La cuenta debe ser una cuenta imputable de la empresa actual.',
            'lines.*.debit.regex' => 'El débito debe ser un número positivo con máximo dos decimales.',
            'lines.*.credit.regex' => 'El crédito debe ser un número positivo con máximo dos decimales.',
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->validateLines($validator),
            fn (Validator $validator) => $this->validatePeriod($validator),
        ];
    }

    public function fiscalPeriod(): FiscalPeriod
    {
        return $this->fiscalPeriod;
    }

    /**
     * Each line carries either a debit or a credit, and the entry must
     * balance (double-entry principle).
     */
    private function validateLines(Validator $validator): void
    {
        $lines = $this->input('lines');

        if (! is_array($lines) || $validator->errors()->has('lines')) {
            return;
        }

        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($lines as $index => $line) {
            if ($validator->errors()->hasAny(["lines.{$index}.debit", "lines.{$index}.credit"])) {
                continue;
            }

            $debit = Money::toCents($line['debit'] ?? null);
            $credit = Money::toCents($line['credit'] ?? null);

            if (($debit > 0) === ($credit > 0)) {
                $validator->errors()->add(
                    "lines.{$index}",
                    'La línea '.($index + 1).' debe tener un débito o un crédito mayor que cero, pero no ambos.',
                );
            }

            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        $lineErrors = collect($validator->errors()->keys())->contains(fn (string $key) => str_starts_with($key, 'lines.'));

        if (! $lineErrors && $totalDebit !== $totalCredit) {
            $validator->errors()->add(
                'lines',
                'El asiento no cuadra: débitos '.Money::format($totalDebit).', créditos '.Money::format($totalCredit).'.',
            );
        }
    }

    /**
     * The entry date must fall inside an open period of the active client.
     */
    private function validatePeriod(Validator $validator): void
    {
        if ($validator->errors()->has('date')) {
            return;
        }

        $period = $this->activeClient()->fiscalPeriods()
            ->overlapping($this->input('date'), $this->input('date'))
            ->first();

        if ($period === null) {
            $validator->errors()->add('date', 'La fecha no pertenece a ningún periodo fiscal de la empresa actual.');
        } elseif ($period->status !== PeriodStatus::Abierto) {
            $validator->errors()->add('date', "La fecha pertenece al periodo \"{$period->name}\", que está cerrado.");
        } else {
            $this->fiscalPeriod = $period;
        }
    }

    private function activeClient(): Client
    {
        return app(ActiveClient::class)->get();
    }
}
