<?php

namespace App\Http\Requests\Clients;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('client'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'tax_id' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('clients', 'tax_id')
                    ->where('firm_id', $this->user()->firm_id)
                    ->ignore($this->route('client')),
            ],
            'accountant_ids' => ['array'],
            'accountant_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('firm_id', $this->user()->firm_id)
                        ->where('role', UserRole::Contador->value);
                }),
            ],
        ];
    }
}
