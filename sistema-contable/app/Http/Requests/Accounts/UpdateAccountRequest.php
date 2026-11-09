<?php

namespace App\Http\Requests\Accounts;

use App\Enums\AccountNature;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAccountRequest extends FormRequest
{
    /**
     * Access is already guaranteed by the active.client middleware and the
     * account route binding, which only resolves accounts of that client.
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
        $account = $this->route('account');

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('accounts', 'code')
                    ->where('client_id', $account->client_id)
                    ->ignore($account),
            ],
            'name' => ['required', 'string', 'max:255'],
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
            'code.unique' => 'Ya existe una cuenta con ese código en la empresa actual.',
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->boolean('is_postable') && $this->route('account')->children()->exists()) {
                    $validator->errors()->add(
                        'is_postable',
                        'Una cuenta con subcuentas es de agrupación y no puede ser imputable.',
                    );
                }
            },
        ];
    }
}
