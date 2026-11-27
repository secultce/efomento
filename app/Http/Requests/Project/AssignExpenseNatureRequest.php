<?php

namespace App\Http\Requests\Project;

use App\Enums\ExpenseNature;
use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignExpenseNatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(Role::fomentoRoles()) ?? false;
    }

    public function rules(): array
    {
        return [
            'selected_projects' => ['required', 'array', 'min:1'],
            'selected_projects.*' => ['integer', 'exists:projects,id'],
            'expense_nature' => ['required', Rule::enum(ExpenseNature::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'selected_projects.required' => 'Selecione pelo menos um projeto.',
            'selected_projects.min' => 'Selecione pelo menos um projeto.',
            'selected_projects.*.exists' => 'Um dos projetos selecionados não foi encontrado.',
            'expense_nature.required' => 'Selecione a natureza da despesa.',
            'expense_nature.enum' => 'A natureza da despesa selecionada é inválida.',
        ];
    }

    public function expenseNature(): ExpenseNature
    {
        return ExpenseNature::from($this->validated('expense_nature'));
    }
}
