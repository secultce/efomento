<?php

namespace Tests\Feature;

use App\Http\Requests\Auth\VerifyTwoFactorCodeRequest;
use App\Http\Requests\Diligence\StoreDiligenceMessageRequest;
use App\Http\Requests\Document\DocumentStoreRequest;
use App\Http\Requests\Formalization\FormalizationStoreRequest;
use App\Http\Requests\Monitoring\MonitoringStoreRequest;
use App\Http\Requests\Opening\OpeningUpdateRequest;
use App\Http\Requests\Project\CreateProjectDocumentRequest;
use App\Http\Requests\User\StoreUserRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ValidationLocalizationTest extends TestCase
{
    public function test_application_defaults_to_brazilian_portuguese(): void
    {
        $this->assertSame('pt_BR', app()->getLocale());
        $this->assertSame('pt_BR', config('app.fallback_locale'));
    }

    public function test_json_validation_errors_and_summary_are_in_portuguese(): void
    {
        $this->postJson('/login', [])->assertUnprocessable()->assertExactJson([
            'message' => 'É obrigatória a indicação de um valor para o campo e-mail. (e mais 1 erro)',
            'errors' => [
                'email' => ['É obrigatória a indicação de um valor para o campo e-mail.'],
                'password' => ['É obrigatória a indicação de um valor para o campo senha.'],
            ],
        ]);
    }

    #[DataProvider('invalidFormFields')]
    public function test_form_errors_use_portuguese_field_names(
        string $requestClass,
        array $data,
        string $field,
        string $expectedMessage,
    ): void {
        $request = $requestClass::create('/', 'POST', $data);
        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());

        $this->assertSame($expectedMessage, $validator->errors()->first($field));
    }

    public static function invalidFormFields(): array
    {
        return [
            'password length' => [
                StoreUserRequest::class,
                ['password' => 'short', 'password_confirmation' => 'short'],
                'password',
                'O campo senha deve conter no mínimo 8 caracteres.',
            ],
            'verification code' => [
                VerifyTwoFactorCodeRequest::class,
                ['code' => 'invalid'],
                'code',
                'O formato do valor para o campo código de verificação é inválido.',
            ],
            'recipient email' => [
                StoreDiligenceMessageRequest::class,
                ['to_email' => 'invalid'],
                'to_email',
                'O campo e-mail do destinatário não contém um endereço de e-mail válido.',
            ],
            'formalization date' => [
                FormalizationStoreRequest::class,
                ['asjur_received_at' => 'invalid'],
                'asjur_received_at',
                'O campo data de recebimento na ASJUR não contém uma data válida.',
            ],
            'supervisor collection' => [
                OpeningUpdateRequest::class,
                ['opening' => ['supervisors' => 'invalid']],
                'opening.supervisors',
                'O campo fiscais deve conter uma coleção de elementos.',
            ],
            'nested supervisor type' => [
                OpeningUpdateRequest::class,
                ['opening' => ['supervisors' => [['type' => 123]]]],
                'opening.supervisors.0.type',
                'O campo tipo de fiscal deve conter texto.',
            ],
            'nested technical opinion' => [
                MonitoringStoreRequest::class,
                ['technical_opinions' => [['suite_number' => []]]],
                'technical_opinions.0.suite_number',
                'O campo número do parecer no SUITE deve conter texto.',
            ],
            'nested document image' => [
                DocumentStoreRequest::class,
                ['images' => [['path' => 'invalid']]],
                'images.0.path',
                'O formato do valor para o campo caminho da imagem é inválido.',
            ],
            'nested header image' => [
                CreateProjectDocumentRequest::class,
                ['header_images' => [['id' => 'invalid']]],
                'header_images.0.id',
                'O campo imagem do cabeçalho deve conter um número inteiro.',
            ],
        ];
    }

    public function test_related_field_names_are_translated_in_date_errors(): void
    {
        $validator = Validator::make([
            'validity_start_at' => '2026-09-16',
            'validity_end_at' => '2026-09-15',
        ], (new FormalizationStoreRequest)->rules());

        $this->assertSame(
            'O campo data final da vigência deve conter uma data posterior ou igual a data inicial da vigência.',
            $validator->errors()->first('validity_end_at'),
        );
    }
}
