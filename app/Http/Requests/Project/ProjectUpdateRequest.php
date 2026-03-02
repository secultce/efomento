<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $project = $this->route('project');

        return [
            'nup' => [
                'sometimes',
                'string',
                Rule::unique('projects', 'nup')->ignore($project->id),
            ],
            'name' => 'sometimes|string',
            'project_url' => 'nullable|string',
            'external_id' => 'nullable|string',
            'total_project_amount' => 'nullable|numeric',
            'total_commitment_amount' => 'nullable|numeric',
            'installments' => 'nullable|integer',
            'process_manager' => 'nullable|string',
            'process_manager_email' => 'nullable|email',
            'creditor_registration_nup' => 'nullable|string',
            'creditor_registration_request_date' => 'nullable|date',
        ];
    }
}