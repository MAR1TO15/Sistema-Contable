<?php

namespace App\Http\Requests\FiscalPeriods;

use App\Services\ActiveClient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreFiscalPeriodRequest extends FormRequest
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
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('fiscal_periods', 'name')->where('client_id', $this->clientId()),
            ],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after:start_date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'Ya existe un periodo con ese nombre en la empresa actual.',
            'end_date.after' => 'La fecha final debe ser posterior a la fecha inicial.',
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                // Only check overlaps once both dates are valid.
                if ($validator->errors()->hasAny(['start_date', 'end_date'])) {
                    return;
                }

                $overlapping = app(ActiveClient::class)->get()->fiscalPeriods()
                    ->overlapping($this->input('start_date'), $this->input('end_date'))
                    ->first();

                if ($overlapping !== null) {
                    $validator->errors()->add(
                        'start_date',
                        "Las fechas se traslapan con el periodo \"{$overlapping->name}\" "
                        ."({$overlapping->start_date->format('d/m/Y')} - {$overlapping->end_date->format('d/m/Y')}).",
                    );
                }
            },
        ];
    }

    private function clientId(): int
    {
        return app(ActiveClient::class)->get()->id;
    }
}
