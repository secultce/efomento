<?php

namespace App\Http\Requests\Opportunity;
use Illuminate\Foundation\Http\FormRequest;

class OpportunityStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nup' => 'required|string|unique:opportunities,nup',
            'name' => 'required|string',
            'opportunity_url' => 'nullable|string',
            'external_id' => 'nullable|string',
            'total_opportunity_amount' => 'nullable|numeric',
            'total_commitment_amount' => 'nullable|numeric',
            'installments' => 'nullable|integer',
            'process_manager' => 'nullable|string',
            'process_manager_email' => 'nullable|email',
            'creditor_registration_nup' => 'nullable|string',
            'creditor_registration_request_date' => 'nullable|date',
        ];
    }
}