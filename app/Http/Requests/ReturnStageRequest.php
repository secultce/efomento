<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReturnStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'O motivo da devolução é obrigatório.',
            'reason.min' => 'O motivo deve ter pelo menos 10 caracteres.',
        ];
    }
}
