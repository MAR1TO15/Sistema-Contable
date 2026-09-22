<?php

namespace App\Http\Requests\Firms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFirmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('firm'));
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
                Rule::unique('firms', 'tax_id')->ignore($this->route('firm')),
            ],
        ];
    }
}
