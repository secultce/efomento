<?php

namespace App\Http\Requests\Formalization;

use App\Enums\DeliberationType;
use App\Enums\ReportStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FormalizationStoreRequest extends FormRequest
{
    private const OFFICIAL_GAZETTE_FILE_MAX_SIZE = 10240;

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
            'term_signed_at' => ['nullable', 'date'],
            'sent_to_office_at' => ['nullable', 'date'],
            'signed_by_office_at' => ['nullable', 'date'],

            'sacc_number' => ['nullable', 'string', 'max:255'],
            'cge_atende_ticket' => ['nullable', 'string', 'max:255'],
            'deliberation' => ['nullable', Rule::enum(DeliberationType::class)],

            'sent_to_chief_of_staff_at' => ['nullable', 'date'],
            'official_gazette_published_at' => ['nullable', 'date'],

            'validity_start_at' => ['nullable', 'date'],
            'validity_end_at' => ['nullable', 'date', 'after_or_equal:validity_start_at'],

            'official_gazette_file' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:'.self::OFFICIAL_GAZETTE_FILE_MAX_SIZE],
        ];
    }

    public function messages(): array
    {
        return [
            'report_status.enum' => 'Informe regularidade e inadimplência possui um valor inválido.',
            'deliberation.enum' => 'Deliberação possui um valor inválido.',
            'validity_end_at.after_or_equal' => 'A data final da vigência deve ser maior ou igual à data inicial.',
            'official_gazette_file.file' => 'O anexo do Diário Oficial deve ser um arquivo válido.',
            'official_gazette_file.mimes' => 'O anexo do Diário Oficial deve ser um arquivo PDF, DOC ou DOCX.',
            'official_gazette_file.max' => 'O anexo do Diário Oficial deve ter no máximo 10MB.',
        ];
    }
}
