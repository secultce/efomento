<?php

namespace App\Http\Requests\Formalization;

use App\Enums\CgeAtendeStatus;
use App\Enums\DeliberationType;
use App\Enums\ReportStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FormalizationStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asjur_finalistic_processing_date' => ['nullable', 'date'],
            'asjur_received_at' => ['nullable', 'date'],
            'process_assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'report_status' => ['nullable', Rule::enum(ReportStatus::class)],
            'eparcerias_certificate_date' => ['nullable', 'date'],

            'asjur_processing_date' => ['nullable', 'date'],
            'term_number' => ['nullable', 'string', 'max:255'],

            'term_signed_at' => ['nullable', 'date'],
            'sent_to_office_at' => ['nullable', 'date'],
            'signed_by_office_at' => ['nullable', 'date'],

            'sacc_number' => ['nullable', 'string', 'max:255'],
            'cge_atende_ticket' => ['nullable', Rule::enum(CgeAtendeStatus::class)],
            'deliberation' => ['nullable', Rule::enum(DeliberationType::class)],

            'sent_to_chief_of_staff_at' => ['nullable', 'date'],
            'official_gazette_published_at' => ['nullable', 'date'],

            'validity_start_at' => ['nullable', 'date'],
            'validity_end_at' => ['nullable', 'date', 'after_or_equal:validity_start_at'],
        ];
    }

    public function messages(): array
    {
        return [
            'report_status.enum' => 'Informe regularidade e inadimplência possui um valor inválido.',
            'cge_atende_ticket.enum' => 'Chamado CGE atende possui um valor inválido.',
            'deliberation.enum' => 'Deliberação possui um valor inválido.',
            'validity_end_at.after_or_equal' => 'A data final da vigência deve ser maior ou igual à data inicial.',
        ];
    }
}
