<?php

namespace App\Http\Requests\Installment;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;

class InstallmentImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(Role::paymentRoles());
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ];
    }
}
