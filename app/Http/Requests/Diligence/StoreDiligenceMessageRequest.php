<?php

namespace App\Http\Requests\Diligence;

use Illuminate\Foundation\Http\FormRequest;

class StoreDiligenceMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'min:20'],
            'to_email' => ['required', 'email'],
        ];
    }
}
