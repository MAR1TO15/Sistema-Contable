<?php

namespace App\Http\Requests\FiscalPeriods;

use App\Enums\AccountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClosePeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('close', $this->route('period'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'equity_account_id' => [
                'required',
                'integer',
                Rule::exists('accounts', 'id')
                    ->where('client_id', $this->route('period')->client_id)
                    ->where('is_postable', 1)
                    ->where('type', AccountType::Patrimonio->value),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'equity_account_id.required' => 'Elige la cuenta de patrimonio que recibirá el resultado del periodo.',
            'equity_account_id.exists' => 'La cuenta debe ser una cuenta imputable de patrimonio de la empresa actual.',
        ];
    }
}
