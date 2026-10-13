<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Http\Requests\Opening\OpeningStoreRequest;
use App\Models\Bank;
use App\Models\Document;
use App\Models\Notice;
use App\Models\Opening;
use App\Models\User;
use App\Services\Documents\DocumentPlaceholderResolver;
use App\Services\SpreadsheetImportService;
use Database\Seeders\BankSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role as PermissionRole;
use Tests\TestCase;

class BankTest extends TestCase
{
    use RefreshDatabase;

    public function test_bank_migration_can_be_rolled_back_and_reapplied(): void
    {
        $migration = require database_path('migrations/2026_10_13_000001_create_banks_table.php');
        $migration->down();

        $this->assertFalse(Schema::hasTable('banks'));
        $this->assertTrue(Schema::hasColumn('openings', 'bank'));
        $this->assertFalse(Schema::hasColumn('openings', 'bank_id'));

        $migration->up();

        $this->assertTrue(Schema::hasColumn('openings', 'bank_id'));
        $this->assertFalse(Schema::hasColumn('openings', 'bank'));
        $this->assertNotNull(Bank::where('code', '001')->first());
    }

    public function test_catalog_is_loaded_locally_and_reseeding_preserves_referenced_ids(): void
    {
        Http::preventStrayRequests();
        $catalog = json_decode(file_get_contents(database_path('seeders/data/banks.json')), true, flags: JSON_THROW_ON_ERROR);
        $bank = Bank::where('code', '001')->firstOrFail();
        $opening = Opening::factory()->create(['bank_id' => $bank->id]);
        $bank->update(['name' => 'Nome desatualizado']);

        $this->seed(BankSeeder::class);
        $this->seed(BankSeeder::class);

        $this->assertDatabaseCount('banks', count($catalog));
        $this->assertSame($bank->id, $opening->fresh()->bank->id);
        $this->assertSame('001', $bank->fresh()->code);
        $this->assertSame('Banco do Brasil S.A.', $bank->fresh()->name);
        $this->assertTrue(Bank::whereNull('code')->exists());
        Http::assertNothingSent();
    }

    public function test_selected_bank_is_saved_and_returned_with_local_options(): void
    {
        Http::preventStrayRequests();
        foreach (Role::cases() as $role) {
            PermissionRole::findOrCreate($role->value, 'web');
        }

        $bank = Bank::where('code', '001')->firstOrFail();
        $opening = Opening::factory()->create(['bank_id' => null]);
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('projects.openings.update', [$opening->project, $opening]), [
            'opening' => ['bank_id' => $bank->id],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame($bank->id, $opening->fresh()->bank_id);

        $this->get(route('notices.projects.show', [$opening->project->notice, $opening->project]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('project.opening.bank_id', $bank->id)
                ->where('project.opening.bank.name', $bank->name)
                ->has('banks', Bank::count())
                ->where('banks', fn ($banks) => collect($banks)->contains(
                    fn ($option) => $option['id'] === $bank->id && $option['code'] === '001'
                ))
            );

        Http::assertNothingSent();
    }

    #[DataProvider('invalidBanks')]
    public function test_update_rejects_invalid_bank_ids(mixed $value): void
    {
        $opening = Opening::factory()->create();
        $originalBankId = $opening->bank_id;

        $this->actingAs(User::factory()->create())
            ->patch(route('projects.openings.update', [$opening->project, $opening]), [
                'opening' => ['bank_id' => $value],
            ])
            ->assertSessionHasErrors('opening.bank_id');

        $this->assertSame($originalBankId, $opening->fresh()->bank_id);
    }

    public static function invalidBanks(): array
    {
        return [
            'unknown id' => [999999],
            'free text' => ['Banco inventado'],
        ];
    }

    public function test_store_validation_accepts_only_existing_bank_ids(): void
    {
        $rules = (new OpeningStoreRequest)->rules()['bank_id'];

        $this->assertTrue(Validator::make(['bank_id' => Bank::firstOrFail()->id], ['bank_id' => $rules])->passes());
        $this->assertTrue(Validator::make(['bank_id' => null], ['bank_id' => $rules])->passes());
        $this->assertTrue(Validator::make(['bank_id' => 999999], ['bank_id' => $rules])->fails());
    }

    public function test_bank_can_be_cleared_while_saving_a_draft(): void
    {
        $opening = Opening::factory()->create();

        $this->actingAs(User::factory()->create())
            ->patch(route('projects.openings.update', [$opening->project, $opening]), [
                'opening' => ['bank_id' => null],
            ])->assertSessionHasNoErrors();

        $this->assertNull($opening->fresh()->bank_id);
    }

    public function test_document_placeholder_uses_the_bank_name(): void
    {
        $bank = Bank::where('code', '001')->firstOrFail();
        $opening = Opening::factory()->create(['bank_id' => $bank->id]);
        $document = Document::factory()->create([
            'project_id' => $opening->project_id,
            'notice_id' => $opening->project->notice_id,
            'body' => 'Banco: [bank]',
        ]);

        $resolver = app(DocumentPlaceholderResolver::class);
        $this->assertSame('Banco: '.$bank->name, $resolver->resolve($document));

        $opening->update(['bank_id' => null]);
        $this->assertSame('Banco: ', $resolver->resolve($document->fresh()));
    }

    #[DataProvider('importBanks')]
    public function test_spreadsheet_import_resolves_banks_from_the_catalog(string $value, ?string $code): void
    {
        $notice = Notice::factory()->create();
        $user = User::factory()->create();
        if ($value === 'Banco desconhecido') {
            Log::shouldReceive('warning')->once()->with('spreadsheet.import.bank_not_found', ['bank' => $value]);
        }

        $project = app(SpreadsheetImportService::class)->processRow([
            'LINK FICHA DE INSCRIÇÃO' => 'https://mapacultural.secult.ce.gov.br/inscricao/123456',
            'CPF / CNPJ DO PROPONENTE' => '52998224725',
            'NOME COMPLETO / RAZÃO SOCIAL DO PROPONENTE' => 'Agente de teste',
            'N° DO PROCESSO (NUP)' => '12345678901234567',
            'BANCO' => $value,
        ], false, $user->id, $notice->id);

        $this->assertNotNull($project);
        $this->assertSame($code, $project->opening->bank?->code);
        if ($code === null) {
            $this->assertNull($project->opening->bank_id);
        }
    }

    public static function importBanks(): array
    {
        return [
            'COMPE' => ['001', '001'],
            'unpadded COMPE' => ['1', '001'],
            'full name' => ['Banco do Brasil S.A.', '001'],
            'short name' => ['BCO DO BRASIL S.A.', '001'],
            'spreadsheet name' => ['BANCO DO BRASIL', '001'],
            'accents' => ['itau unibanco sa', '341'],
            'unknown name' => ['Banco desconhecido', null],
            'empty' => ['', null],
        ];
    }
}
