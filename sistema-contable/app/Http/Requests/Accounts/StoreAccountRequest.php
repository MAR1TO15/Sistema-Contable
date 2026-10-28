<?php

namespace App\Http\Requests\Accounts;

use App\Enums\AccountNature;
use App\Enums\AccountType;
use App\Services\ActiveClient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountRequest extends FormRequest
{
    /**
     * Access is already guaranteed by the active.client middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $clientId = app(ActiveClient::class)->get()->id;

        return [
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('accounts', 'id')
                    ->where('client_id', $clientId)
                    ->where('is_postable', 0),
            ],
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('accounts', 'code')->where('client_id', $clientId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required_without:parent_id', 'nullable', Rule::enum(AccountType::class)],
            'nature' => ['nullable', Rule::enum(AccountNature::class)],
            'is_postable' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'parent_id.exists' => 'La cuenta padre debe ser una cuenta de agrupación (no imputable) de la empresa actual.',
            'code.unique' => 'Ya existe una cuenta con ese código en la empresa actual.',
            'type.required_without' => 'Indica el tipo de la cuenta cuando no tiene cuenta padre.',
        ];
    }
}
