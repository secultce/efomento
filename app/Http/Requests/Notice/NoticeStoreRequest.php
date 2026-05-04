<?php

namespace App\Http\Requests\Notice;

use Illuminate\Foundation\Http\FormRequest;

class NoticeStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nup' => 'required|string|unique:notices,nup',
            'name' => 'required|string',
            'notice_url' => 'nullable|string',
            'external_id' => 'nullable|string',
            'total_notice_amount' => 'nullable|numeric',
            'total_commitment_amount' => 'nullable|numeric',
            'installments' => 'nullable|integer',
            'process_manager' => 'nullable|string',
            'process_manager_email' => 'nullable|email',
            'creditor_registration_nup' => 'nullable|string',
            'creditor_registration_request_date' => 'nullable|date',
        ];
    }
}
