<?php

namespace App\Http\Requests\Formalization;

use App\Enums\DeliberationType;
use App\Enums\ReportStatus;
use App\Enums\TermStatus;
use Illuminate\Validation\Rule;

class FormalizationUpdateRequest extends FormalizationStoreRequest
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
            'process_assigned_to' => ['nullable', 'string', 'max:255'],
            'report_status' => ['nullable', Rule::enum(ReportStatus::class)],
            'eparcerias_certificate_date' => ['nullable', 'date'],

            'asjur_processing_date' => ['nullable', 'date'],
            'responsible_at_asjur' => ['nullable', 'string', 'max:255'],
            'term_number' => ['nullable', 'string', 'max:255'],

            'term_signature_sent_at' => ['nullable', 'date'],
            'term_status' => ['nullable', Rule::enum(TermStatus::class)],
            'term_signed_at' => ['nullable', 'date'],
            'sent_to_office_at' => ['nullable', 'date'],
            'signature_status_office' => ['nullable', Rule::enum(TermStatus::class)],
            'signed_by_office_at' => ['nullable', 'date'],

            'sacc_number' => ['nullable', 'string', 'max:255'],
            'cge_atende_ticket' => ['nullable', 'string', 'max:255'],
            'deliberation' => ['nullable', Rule::enum(DeliberationType::class)],

            'sent_to_chief_of_staff_at' => ['nullable', 'date'],
            'official_gazette_published_at' => ['nullable', 'date'],

            'validity_start_at' => ['nullable', 'date'],
            'validity_end_at' => ['nullable', 'date', 'after_or_equal:validity_start_at'],

            'legal_opinion_date' => ['nullable', 'date'],
        ];
    }
}
