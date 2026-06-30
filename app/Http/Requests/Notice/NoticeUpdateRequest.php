<?php

namespace App\Http\Requests\Notice;

use App\Enums\InstrumentType;
use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class NoticeUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(Role::fomentoRoles());
    }

    public function messages(): array
    {
        return [
            'nup.unique' => 'Este número de processo mãe já está em uso.',
            'budget_allocation_nup.unique' => 'Este número de processo de dotação orçamentária já está em uso.',
        ];
    }

    public function rules(): array
    {
        $notice = $this->route('notice');

        return [
            'nup' => [
                'sometimes',
                'string',
                Rule::unique('notices', 'nup')->ignore($notice->id),
            ],
            'instrument_type' => ['nullable', new Enum(InstrumentType::class)],
            'name' => 'sometimes|string',
            'notice_url' => 'nullable|string',
            'external_id' => 'nullable|string',
            'total_notice_amount' => 'nullable|numeric',
            'total_commitment_amount' => 'nullable|numeric',
            'installments' => 'nullable|integer|min:0',
            'process_manager' => 'nullable|string',
            'process_manager_email' => 'nullable|email',
            'budget_allocation_nup' => [
                'nullable',
                'string',
                Rule::unique('notices', 'budget_allocation_nup')->ignore($notice->id),
            ],
            'budget_allocation_request_date' => 'nullable|date',
            'creditor_registration_nup' => 'nullable|string',
            'creditor_registration_request_date' => 'nullable|date',
        ];
    }
}
