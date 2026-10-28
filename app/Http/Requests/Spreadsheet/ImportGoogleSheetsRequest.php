<?php

namespace App\Http\Requests\Spreadsheet;

use App\Enums\Role;
use App\Services\GoogleSheetsService;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportGoogleSheetsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(Role::SUPER_ADMIN) ?? false;
    }

    public function rules(): array
    {
        return [
            'spreadsheet_url' => ['bail', 'required', 'string', 'max:2048', function (string $attribute, mixed $value, Closure $fail) {
                if (GoogleSheetsService::extractSpreadsheetId($value) === null) {
                    $fail('Informe um link válido do Google Sheets ou o ID da planilha.');
                }
            }],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'fallback_notice_id' => ['nullable', 'integer', Rule::exists('notices', 'id')->withoutTrashed()],
        ];
    }

    public function messages(): array
    {
        return [
            'fallback_notice_id.exists' => 'O edital informado não existe no e-fomento. Use o ID interno do edital, não o ID do Mapas, ou deixe o campo vazio para identificar o edital pela planilha.',
        ];
    }

    public function attributes(): array
    {
        return [
            'spreadsheet_url' => 'link ou ID da planilha',
            'user_id' => 'usuário responsável',
            'fallback_notice_id' => 'ID do edital',
        ];
    }

    public function spreadsheetId(): string
    {
        return GoogleSheetsService::extractSpreadsheetId($this->validated('spreadsheet_url'));
    }
}
