<?php

namespace App\Http\Requests\Project;

use App\Enums\DocumentType;
use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateProjectDocumentRequest extends FormRequest
{
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
            'header_images.*.file' => ['nullable', 'image'],
            'header_images.*._delete' => ['nullable', 'in:1'],

            'footer_images' => ['nullable', 'array'],
            'footer_images.*.id' => ['nullable', 'integer'],
            'footer_images.*.file' => ['nullable', 'image'],
            'footer_images.*._delete' => ['nullable', 'in:1'],

            'header_layout' => ['nullable', 'in:none,three,full'],
            'footer_layout' => ['nullable', 'in:none,three,full'],
        ];
    }

    private function documentType(): ?DocumentType
    {
        $type = $this->input('type');

        return is_string($type) ? DocumentType::tryFrom($type) : null;
    }
}
