<?php

namespace App\Http\Requests\Opening;

use Illuminate\Foundation\Http\FormRequest;

class OpeningUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $opening = $this->input('opening', []);

        if (isset($opening['opening_nup'])) {
            $opening['opening_nup'] = preg_replace('/\D/', '', $opening['opening_nup']) ?: null;
        }

        if (isset($opening['allocation_number'])) {
            $opening['allocation_number'] = preg_replace('/\D/', '', $opening['allocation_number']) ?: null;
        }

        $this->merge(['opening' => $opening]);
    }

    public function rules(): array
    {
        return [
            'opening.opening_nup' => ['sometimes', 'nullable', 'digits:17'],
            'opening.opening_date' => ['sometimes', 'nullable', 'date'],
            'opening.agent_status' => ['sometimes', 'nullable', 'string'],
            'opening.opened_by' => ['sometimes', 'nullable', 'string'],

            'opening.creditor_number' => ['sometimes', 'nullable', 'string'],

            'opening.allocation_code' => ['sometimes', 'nullable', 'string'],
            'opening.allocation_number' => ['sometimes', 'nullable', 'string'],

            'opening.bank' => ['sometimes', 'nullable', 'string'],
            'opening.account_type' => ['sometimes', 'nullable', 'string'],
            'opening.branch' => ['sometimes', 'nullable', 'string'],
            'opening.account' => ['sometimes', 'nullable', 'string'],

            'opening.supervisors' => ['array'],
            'opening.supervisors.*.id' => ['nullable', 'exists:users,id'],
            'opening.supervisors.*.type' => ['nullable', 'string', 'in:principal,alternate'],
            'opening.supervisors.*.registration_number' => ['nullable', 'string'],

            'formalization.report_status' => ['nullable', 'string'],
            'formalization.eparcerias_certificate_date' => ['nullable', 'date'],

            'agent.secondary_email' => ['nullable', 'email'],
            'agent.secondary_phone' => ['nullable', 'string'],
        ];
    }
}
