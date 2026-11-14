<?php

namespace App\Http\Requests\Project;

use App\Enums\DocumentType;
use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateProjectDocumentRequest extends FormRequest
{
    private const IMAGE_FILE_RULES = [
        'nullable',
        'file',
        'mimes:jpeg,jpg,png,webp',
        'mimetypes:image/jpeg,image/png,image/webp',
    ];

    private const IMAGE_FORMAT_MESSAGE = 'Apenas imagens nos formatos .jpg, .jpeg, .png e .webp são permitidas.';

    public function authorize(): bool
    {
        $type = $this->documentType();

        if ($type?->isBudgetOpinion()) {
            return $this->user()?->hasAnyRole(Role::budgetRoles()) ?? false;
        }

        if ($type?->isJuridicalReference()) {
            return $this->user()?->hasAnyRole(Role::legalAnalysisRoles()) ?? false;
        }

        return true;
    }

    public function rules(): array
    {
        $isNoticeLevel = $this->documentType()?->isNoticeLevel() ?? false;

        return [
            'type' => ['required', Rule::enum(DocumentType::class)],

            'notice_id' => [
                Rule::requiredIf($isNoticeLevel),
                'nullable',
                'exists:notices,id',
            ],

            'selected_projects' => [
                Rule::requiredIf(! $isNoticeLevel),
                'array',
                'min:1',
            ],
            'selected_projects.*' => ['exists:projects,id'],

            'content' => ['required', 'string'],

            'header_images' => ['nullable', 'array'],
            'header_images.*.id' => ['nullable', 'integer'],
            'header_images.*.file' => self::IMAGE_FILE_RULES,
            'header_images.*._delete' => ['nullable', 'in:1'],

            'footer_images' => ['nullable', 'array'],
            'footer_images.*.id' => ['nullable', 'integer'],
            'footer_images.*.file' => self::IMAGE_FILE_RULES,
            'footer_images.*._delete' => ['nullable', 'in:1'],

            'header_layout' => ['nullable', 'in:none,three,full'],
            'footer_layout' => ['nullable', 'in:none,three,full'],
        ];
    }

    public function messages(): array
    {
        return [
            'header_images.*.file.file' => self::IMAGE_FORMAT_MESSAGE,
            'header_images.*.file.mimes' => self::IMAGE_FORMAT_MESSAGE,
            'header_images.*.file.mimetypes' => self::IMAGE_FORMAT_MESSAGE,
            'footer_images.*.file.file' => self::IMAGE_FORMAT_MESSAGE,
            'footer_images.*.file.mimes' => self::IMAGE_FORMAT_MESSAGE,
            'footer_images.*.file.mimetypes' => self::IMAGE_FORMAT_MESSAGE,
        ];
    }

    private function documentType(): ?DocumentType
    {
        $type = $this->input('type');

        return is_string($type) ? DocumentType::tryFrom($type) : null;
    }
}
