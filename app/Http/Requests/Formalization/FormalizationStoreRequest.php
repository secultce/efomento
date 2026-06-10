<?php

namespace App\Http\Requests\Formalization;

use App\Enums\DeliberationType;
use App\Enums\ReportStatus;
use App\Enums\TermStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FormalizationStoreRequest extends FormRequest
{
    public const REQUIRED_FIELDS = [
        'report_status' => 'Informe regularidade e inadimplência',
        'term_number' => 'Número do termo',
        'term_status' => 'Status do termo',
        'signature_status_office' => 'Status de assinatura pelo Gabinete',
        'deliberation' => 'Deliberação',
    ];

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
            'report_status' => ['required', Rule::enum(ReportStatus::class)],
            'eparcerias_certificate_date' => ['nullable', 'date'],

            'asjur_processing_date' => ['nullable', 'date'],
            'responsible_at_asjur' => ['nullable', 'string', 'max:255'],
            'term_number' => ['required', 'string', 'max:255'],

            'term_signature_sent_at' => ['nullable', 'date'],
            'term_status' => ['required', Rule::enum(TermStatus::class)],
            'term_signed_at' => ['nullable', 'date'],
            'sent_to_office_at' => ['nullable', 'date'],
            'signature_status_office' => ['required', Rule::enum(TermStatus::class)],
            'signed_by_office_at' => ['nullable', 'date'],

            'sacc_number' => ['nullable', 'string', 'max:255'],
            'cge_atende_ticket' => ['nullable', 'string', 'max:255'],
            'deliberation' => ['required', Rule::enum(DeliberationType::class)],

            'sent_to_chief_of_staff_at' => ['nullable', 'date'],
            'official_gazette_published_at' => ['nullable', 'date'],

            'validity_start_at' => ['nullable', 'date'],
            'validity_end_at' => ['nullable', 'date', 'after_or_equal:validity_start_at'],

            'legal_opinion_date' => ['nullable', 'date'],

            'official_gazette_file' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:'.self::OFFICIAL_GAZETTE_FILE_MAX_SIZE],
        ];
    }

    public function messages(): array
    {
        return [
            'report_status.required' => 'Informe regularidade e inadimplência é obrigatório.',
            'report_status.enum' => 'Informe regularidade e inadimplência possui um valor inválido.',

            'term_number.required' => 'Número do termo é obrigatório.',

            'term_status.required' => 'Status do termo é obrigatório.',
            'term_status.enum' => 'Status do termo possui um valor inválido.',

            'signature_status_office.required' => 'Status de assinatura pelo Gabinete é obrigatório.',
            'signature_status_office.enum' => 'Status de assinatura pelo Gabinete possui um valor inválido.',

            'deliberation.required' => 'Deliberação é obrigatória.',
            'deliberation.enum' => 'Deliberação possui um valor inválido.',

            'validity_end_at.after_or_equal' => 'A data final da vigência deve ser maior ou igual à data inicial.',

            'official_gazette_file.file' => 'O anexo do Diário Oficial deve ser um arquivo válido.',
            'official_gazette_file.mimes' => 'O anexo do Diário Oficial deve ser um arquivo PDF, DOC ou DOCX.',
            'official_gazette_file.max' => 'O anexo do Diário Oficial deve ter no máximo 10MB.',
        ];
    }
}
