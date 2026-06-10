<?php

namespace App\Http\Requests\Formalization;

class FormalizationUpdateRequest extends FormalizationStoreRequest
{
    public function authorize(): bool
    {
        return true;
    }
}
