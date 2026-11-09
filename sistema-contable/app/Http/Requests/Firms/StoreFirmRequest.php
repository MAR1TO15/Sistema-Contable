<?php

namespace App\Http\Requests\Firms;

use App\Models\Firm;
use Illuminate\Foundation\Http\FormRequest;

class StoreFirmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Firm::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:50', 'unique:firms,tax_id'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
