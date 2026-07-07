<?php

namespace App\Http\Requests\Opening;

use Illuminate\Foundation\Http\FormRequest;

class OpeningStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'exists:projects,id'],

            'opening_nup' => ['nullable', 'string', 'max:255'],
            'opening_date' => ['nullable', 'date'],

            'agent_status' => ['nullable', 'string', 'max:255'],
            'opened_by' => ['nullable', 'string', 'max:255'],

            'allocation_code' => ['nullable', 'string', 'max:255'],
            'allocation_number' => ['nullable', 'string', 'max:255'],

            'bank' => ['nullable', 'string', 'max:255'],
            'account_type' => ['nullable', 'string', 'max:100'],
            'branch' => ['nullable', 'string', 'max:50'],
            'account' => ['nullable', 'string', 'max:50'],

            'is_draft' => ['boolean'],

            'status' => [
                'nullable',
                'in:pendente,em_andamento,concluido,rejeitado',
            ],
        ];
    }
}
