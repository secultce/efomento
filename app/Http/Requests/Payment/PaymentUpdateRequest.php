<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $paymentId = $this->route('payment')?->id;

        return [
            'project_id' => [
                'required',
                'integer',
                'exists:projects,id',
                Rule::unique('payments', 'project_id')->ignore($paymentId),
            ],

            'creditor_requested_at' => [
                'nullable',
                'date',
            ],

            'creditor_registration_number' => [
                'nullable',
                'string',
                'max:50',
            ],

            'communication_sent_at' => [
                'nullable',
                'date',
                'after_or_equal:creditor_requested_at',
            ],

            'contact_notes' => [
                'nullable',
                'string',
            ],
        ];
    }
}