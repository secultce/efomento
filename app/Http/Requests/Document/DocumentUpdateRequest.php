<?php

namespace App\Http\Requests\Document;

use App\Enums\DocumentImagePosition;
use App\Enums\DocumentImageSection;
use App\Enums\DocumentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['nullable', 'string'],
            'status' => ['nullable', Rule::enum(DocumentStatus::class)],
            'images' => ['nullable', 'array'],
            'images.*.section' => ['required_with:images', Rule::enum(DocumentImageSection::class)],
            'images.*.position' => ['required_with:images', Rule::enum(DocumentImagePosition::class)],
            'images.*.path' => ['required_with:images', 'string'],
        ];
    }
}
