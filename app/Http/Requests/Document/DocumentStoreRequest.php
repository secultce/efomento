<?php

namespace App\Http\Requests\Document;

use Illuminate\Foundation\Http\FormRequest;

class DocumentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'             => ['required', 'string'],
            'phase'            => ['required', 'string'],
            'body'             => ['required', 'string'],
            'notice_id'        => ['required', 'exists:notices,id'],
            'project_id'       => ['required', 'exists:projects,id'],
            'images'           => ['nullable', 'array'],
            'images.*.section' => ['required_with:images', 'in:header,footer'],
            'images.*.position'=> ['required_with:images', 'in:left,center,right'],
            'images.*.path'    => ['required_with:images', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'notice_id.required'  => 'O edital é obrigatório.',
            'notice_id.exists'    => 'O edital informado não existe.',
            'project_id.required' => 'O projeto é obrigatório.',
            'project_id.exists'   => 'O projeto informado não existe.',
            'type.required'       => 'O tipo do documento é obrigatório.',
            'phase.required'      => 'A fase do documento é obrigatória.',
            'body.required'       => 'O conteúdo do documento é obrigatório.',
        ];
    }
}
