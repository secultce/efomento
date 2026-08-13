<?php

namespace App\Http\Requests\Opening;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class OpeningUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare inputs for validation (clean non-numeric characters if needed).
     */
    protected function prepareForValidation(): void
    {
        $this->sanitizeNumericOpeningFields(['opening_nup']);
    }

    /**
     * Limpa caracteres não numéricos de campos específicos do bloco 'opening'.
     *
     * @param  array<int, string>  $fields
     */
    private function sanitizeNumericOpeningFields(array $fields): void
    {
        if (! $this->has('opening')) {
            return;
        }

        $opening = $this->input('opening', []);

        foreach ($fields as $field) {
            if (! empty($opening[$field])) {
                $opening[$field] = preg_replace('/\D/', '', $opening[$field]);
            }
        }

        $this->merge(['opening' => $opening]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Dados da Abertura
            'opening.opening_nup' => ['nullable', 'string', 'size:17'],
            'opening.opening_date' => ['nullable', 'date'],
            'opening.agent_status' => ['nullable', 'string', 'max:255'],
            'opening.opened_by' => ['nullable', 'string', 'max:255'],

            // Credor
            'opening.creditor_number' => ['nullable', 'string', 'max:255'],

            // Dados Bancários
            'opening.bank' => ['nullable', 'string', 'max:255'],
            'opening.account_type' => ['nullable', 'string', 'max:255'],
            'opening.branch' => ['nullable', 'string', 'max:255'],
            'opening.account' => ['nullable', 'string', 'max:255'],

            // Fiscais
            'opening.supervisors' => ['nullable', 'array'],
            'opening.supervisors.*.id' => ['nullable', 'integer', 'exists:users,id'],
            'opening.supervisors.*.registration_number' => ['nullable', 'string', 'max:255'],
            'opening.supervisors.*.type' => ['nullable', 'string', 'in:principal,alternate'],

            // Formalização
            'formalization.report_status' => ['nullable', 'string', 'max:255'],
            'formalization.eparcerias_certificate_date' => ['nullable', 'date'],

            // Agente
            'agent.secondary_email' => ['nullable', 'email:rfc', 'max:255'],
            'agent.secondary_phone' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'opening.opening_nup.size' => 'O Número do Processo deve conter exatamente 17 dígitos.',
            'opening.opening_date.date' => 'A Data de abertura do processo deve ser uma data válida.',

            'opening.supervisors.*.id.exists' => 'O fiscal selecionado não foi encontrado no sistema.',
            'opening.supervisors.*.type.in' => 'O tipo de fiscal deve ser titular ou suplente.',

            'formalization.eparcerias_certificate_date.date' => 'A Data da certidão deve ser uma data válida.',

            'agent.secondary_email.email' => 'Insira um endereço de e-mail secundário válido.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'opening.opening_nup' => 'número do processo',
            'opening.opening_date' => 'data de abertura do processo',
            'opening.agent_status' => 'status do agente cultural',
            'opening.opened_by' => 'responsável por abrir o processo',
            'opening.creditor_number' => 'número do cadastro do credor',
            'opening.bank' => 'banco',
            'opening.account_type' => 'tipo de conta',
            'opening.branch' => 'agência',
            'opening.account' => 'conta',
            'opening.supervisors.*.id' => 'fiscal',
            'opening.supervisors.*.registration_number' => 'matrícula do fiscal',
            'formalization.report_status' => 'regularidade e inadimplência',
            'formalization.eparcerias_certificate_date' => 'data da certidão',
            'agent.secondary_email' => 'e-mail secundário',
            'agent.secondary_phone' => 'telefone secundário',
        ];
    }
}
