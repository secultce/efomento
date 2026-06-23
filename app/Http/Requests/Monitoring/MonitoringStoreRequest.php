<?php

namespace App\Http\Requests\Monitoring;

use Illuminate\Foundation\Http\FormRequest;

class MonitoringStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'technical_opinions' => ['nullable', 'array'],
            'technical_opinions.*.suite_number' => ['required_with:technical_opinions', 'string', 'max:255'],
            'technical_opinions.*.processing_date' => ['nullable', 'date'],
            'observations' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'technical_opinions.array' => 'Os pareceres técnicos devem ser uma lista válida.',
            'technical_opinions.*.suite_number.required_with' => 'O número do parecer no SUITE é obrigatório.',
            'technical_opinions.*.suite_number.max' => 'O número do parecer no SUITE deve ter no máximo 255 caracteres.',
            'technical_opinions.*.processing_date.date' => 'A data da tramitação do parecer deve ser uma data válida.',
        ];
    }
}
