<?php

namespace App\Http\Requests\Opening;

use Illuminate\Foundation\Http\FormRequest;

class OpeningUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'opening.opening_nup' => ['sometimes', 'nullable', 'string', 'max:255'],
            'opening.opening_date' => ['sometimes', 'nullable', 'date'],
            'opening.agent_status' => ['sometimes', 'nullable', 'string'],
            'opening.opened_by' => ['sometimes', 'nullable', 'string'],

            'opening.bank' => ['sometimes', 'nullable', 'string'],
            'opening.account_type' => ['sometimes', 'nullable', 'string'],
            'opening.branch' => ['sometimes', 'nullable', 'string'],
            'opening.account' => ['sometimes', 'nullable', 'string'],

            'opening.supervisors' => ['array'],
            'opening.supervisors.*.id' => ['nullable', 'exists:users,id'],
            'opening.supervisors.*.registration_number' => ['nullable', 'string'],

            'formalization.report_status' => ['nullable', 'string'],
            'formalization.eparcerias_certificate_date' => ['nullable', 'date'],

            'agent.secondary_email' => ['nullable', 'email'],
            'agent.secondary_phone' => ['nullable', 'string'],
        ];
    }
}