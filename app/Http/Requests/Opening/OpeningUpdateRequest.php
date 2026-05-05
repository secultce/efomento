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
        if ($this->has('opening')) {
            $this->merge($this->input('opening'));

            $this->request->remove('opening');
        }
    }

    public function rules(): array
    {
        return [
            'project_id' => ['sometimes', 'exists:projects,id'],

            'opening_nup' => ['sometimes', 'nullable', 'string', 'max:255'],
            'opening_date' => ['sometimes', 'nullable', 'date'],

            'agent_status' => ['sometimes', 'nullable', 'string', 'max:255'],
            'opened_by' => ['sometimes', 'nullable', 'string', 'max:255'],

            'bank' => ['sometimes', 'nullable', 'string', 'max:255'],
            'account_type' => ['sometimes', 'nullable', 'string', 'max:100'],
            'branch' => ['sometimes', 'nullable', 'string', 'max:50'],
            'account' => ['sometimes', 'nullable', 'string', 'max:50'],

            'is_draft' => ['sometimes', 'boolean'],

            'status' => [
                'sometimes',
                'in:pendente,em_andamento,concluido,rejeitado'
            ],
        ];
    }
}