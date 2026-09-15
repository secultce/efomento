<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyTwoFactorCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'regex:/\A[0-9]{'.config('two_factor.code_length').'}\z/'],
            'trust_device' => ['sometimes', 'boolean'],
        ];
    }
}
