<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;

class BudgetStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'processing_date_for_codip' => ['nullable', 'date'],
            'processing_date_for_coafi' => ['nullable', 'date'],

            'notice_installment_number' => ['nullable', 'integer', 'min:1'],
            'installment_amount' => ['nullable', 'numeric', 'min:0.01', 'max:99999999.99'],
            'installment_request_date' => ['nullable', 'date'],
            'installment_justification' => ['nullable', 'string'],
            'installment_observations' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'processing_date_for_codip.date' => 'A data de tramitação para a CODIP deve ser uma data válida.',

            'processing_date_for_coafi.date' => 'A data de tramitação para a Coafi deve ser uma data válida.',

            'notice_installment_number.integer' => 'O número da parcela no edital deve ser um número inteiro.',
            'notice_installment_number.min' => 'O número da parcela no edital deve ser maior que zero.',

            'installment_amount.numeric' => 'O valor da parcela deve ser numérico.',
            'installment_amount.min' => 'O valor da parcela deve ser maior que zero.',
            'installment_amount.max' => 'O valor da parcela não pode ser superior a 99.999.999,99.',
            'installment_request_date.date' => 'A data de solicitação da parcela deve ser uma data válida.',

            'installment_justification.string' => 'A justificativa da parcela deve ser um texto válido.',

            'installment_observations.string' => 'As observações da parcela devem ser um texto válido.',
        ];
    }
}
