<?php

namespace App\Http\Requests\Opportunity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use App\Enums\InstrumentType;

class OpportunityUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $opportunity = $this->route('opportunity');

        return [
            'nup' => [
                'sometimes',
                'string',
                Rule::unique('opportunities', 'nup')->ignore($opportunity->id),
            ],
            'instrument_type' => ['nullable', new Enum(InstrumentType::class)],
            'name' => 'sometimes|string',
            'opportunity_url' => 'nullable|string',
            'external_id' => 'nullable|string',
            'total_opportunity_amount' => 'nullable|numeric',
            'total_commitment_amount' => 'nullable|numeric',
            'installments' => 'nullable|integer',
            'process_manager' => 'nullable|string',
            'process_manager_email' => 'nullable|email',
            'budget_allocation_nup' => 'nullable|string',
            'budget_allocation_request_date' => 'nullable|date',
            'creditor_registration_nup' => 'nullable|string',
            'creditor_registration_request_date' => 'nullable|date',
        ];
    }
}