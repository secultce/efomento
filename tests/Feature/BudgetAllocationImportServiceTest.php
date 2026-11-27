<?php

namespace Tests\Feature;

use App\Enums\ExpenseNature;
use App\Enums\Role;
use App\Exceptions\Domain\BusinessRuleException;
use App\Models\Budget;
use App\Models\BudgetAllocation;
use App\Models\Installment;
use App\Models\Notice;
use App\Models\Project;
use App\Models\User;
use App\Services\BudgetAllocationImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class BudgetAllocationImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_budget_allocations_without_associating_a_budget(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        [$notice, $budget, $installment] = $this->createBudget();

        $result = $this->import($notice, $this->csvRow());

        $this->assertSame(1, $result['summary']['processed']);
        $this->assertSame(1, $result['summary']['created']);
        $this->assertSame(0, $result['summary']['updated']);
        $this->assertSame(0, $result['summary']['skipped']);
        $this->assertCount(15, $result['columns']);

        $this->assertDatabaseHas('budget_allocations', [
            'notice_id' => $notice->id,
            'management_unit' => '123/456',
            'work_program' => '131 - Promoção e Desenvolvimento da Arte',
            'objective' => 'Democratizar, fomentar e ampliar o acesso',
            'deliverable' => '1894 - Projeto apoiado',
            'budget_function' => '13 - Cultura',
            'budget_subfunction' => '392 - Difusão cultural',
            'project_activity' => '11607 - Promoção do edital',
            'expense_element' => '339048',
            'expense_nature' => ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA->value,
            'funding_source' => '719',
            'mapp' => '635',
            'finalistic_project' => 'Projeto finalístico',
            'region_code' => '03',
            'planning_macroregion' => 'Cariri',
            'allocation_code' => '123456',
            'allocation_number' => '12345678901234567890123456789012345678901',
            'created_by' => $user->id,
        ]);
        $this->assertDatabaseHas('budgets', [
            'id' => $budget->id,
        ]);
        $this->assertDatabaseHas('installments', [
            'id' => $installment->id,
            'budget_allocation_id' => null,
        ]);
    }

    public function test_it_updates_an_existing_allocation_when_the_same_csv_is_uploaded_again(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        [$notice] = $this->createBudget();

        $this->import($notice, $this->csvRow());
        $originalAllocation = BudgetAllocation::firstOrFail();
        $result = $this->import($notice, $this->csvRow([
            'Objetivo' => 'Objetivo atualizado',
        ]));

        $this->assertSame(0, $result['summary']['created']);
        $this->assertSame(1, $result['summary']['updated']);
        $this->assertDatabaseCount('budget_allocations', 2);
        $this->assertSoftDeleted('budget_allocations', [
            'id' => $originalAllocation->id,
        ]);
        $this->assertSame(1, BudgetAllocation::query()->count());
        $this->assertSame('Objetivo atualizado', BudgetAllocation::first()->objective);
    }

    public function test_it_imports_for_a_notice_without_projects_or_budgets(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $notice = Notice::factory()->create();
        $result = $this->import($notice, $this->csvRow());

        $this->assertSame(1, $result['summary']['created']);
        $this->assertDatabaseHas('budget_allocations', [
            'notice_id' => $notice->id,
            'allocation_code' => '123456',
        ]);
    }

    public function test_import_endpoint_requires_a_budget_role(): void
    {
        $user = User::factory()->create();
        [$notice] = $this->createBudget();

        $this->actingAs($user)
            ->post(route('budget-allocations.import', $notice), [
                'file' => $this->csvFile($this->csvRow()),
            ], [
                'Accept' => 'application/json',
            ])
            ->assertForbidden();
    }

    public function test_import_endpoint_persists_and_returns_the_rows(): void
    {
        $user = User::factory()->create();
        SpatieRole::firstOrCreate([
            'name' => Role::BUDGETARY->value,
            'guard_name' => 'web',
        ]);
        $user->assignRole(Role::BUDGETARY->value);
        [$notice] = $this->createBudget();

        $this->actingAs($user)
            ->post(route('budget-allocations.import', $notice), [
                'file' => $this->csvFile($this->csvRow()),
            ], [
                'Accept' => 'application/json',
            ])
            ->assertOk()
            ->assertJsonPath('summary.created', 1)
            ->assertJsonPath('rows.0.management_unit', '123/456')
            ->assertJsonPath('rows.0.allocation_code', '123456');
    }

    public function test_preview_endpoint_returns_rows_without_importing_them(): void
    {
        $user = User::factory()->create();
        SpatieRole::firstOrCreate([
            'name' => Role::BUDGETARY->value,
            'guard_name' => 'web',
        ]);
        $user->assignRole(Role::BUDGETARY->value);
        $notice = Notice::factory()->create();

        $this->actingAs($user)
            ->post(route('budget-allocations.preview', $notice), [
                'file' => $this->csvFile($this->csvRow()),
            ], [
                'Accept' => 'application/json',
            ])
            ->assertOk()
            ->assertJsonPath('has_existing_allocations', false)
            ->assertJsonPath('rows.0.management_unit', '123/456')
            ->assertJsonPath('rows.0.allocation_code', '123456');

        $this->assertDatabaseCount('budget_allocations', 0);
    }

    public function test_preview_endpoint_indicates_when_the_notice_already_has_allocations(): void
    {
        $user = User::factory()->create();
        SpatieRole::firstOrCreate([
            'name' => Role::BUDGETARY->value,
            'guard_name' => 'web',
        ]);
        $user->assignRole(Role::BUDGETARY->value);
        $notice = Notice::factory()->create();
        BudgetAllocation::factory()->create([
            'notice_id' => $notice->id,
        ]);

        $this->actingAs($user)
            ->post(route('budget-allocations.preview', $notice), [
                'file' => $this->csvFile($this->csvRow()),
            ], [
                'Accept' => 'application/json',
            ])
            ->assertOk()
            ->assertJsonPath('has_existing_allocations', true);
    }

    public function test_preview_endpoint_returns_at_most_five_hundred_rows(): void
    {
        $user = User::factory()->create();
        SpatieRole::firstOrCreate([
            'name' => Role::BUDGETARY->value,
            'guard_name' => 'web',
        ]);
        $user->assignRole(Role::BUDGETARY->value);
        $notice = Notice::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('budget-allocations.preview', $notice), [
                'file' => $this->csvFileWithRows(array_fill(0, 501, $this->csvRow())),
            ], [
                'Accept' => 'application/json',
            ])
            ->assertOk();

        $this->assertCount(500, $response->json('rows'));
        $this->assertDatabaseCount('budget_allocations', 0);
    }

    public function test_preview_endpoint_rejects_a_csv_larger_than_ten_megabytes(): void
    {
        $user = User::factory()->create();
        SpatieRole::firstOrCreate([
            'name' => Role::BUDGETARY->value,
            'guard_name' => 'web',
        ]);
        $user->assignRole(Role::BUDGETARY->value);
        $notice = Notice::factory()->create();

        $this->actingAs($user)
            ->post(route('budget-allocations.preview', $notice), [
                'file' => UploadedFile::fake()->create(
                    'budget-allocations.csv',
                    10241,
                    'text/csv'
                ),
            ], [
                'Accept' => 'application/json',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file')
            ->assertJsonPath('errors.file.0', 'O arquivo CSV deve ter no máximo 10 MB.');
    }

    public function test_it_stores_the_expense_nature_of_each_row(): void
    {
        $this->actingAs(User::factory()->create());
        $notice = Notice::factory()->create();

        $this->importRows($notice, [
            $this->csvRow(['Elemento de Despesa' => '339048 - OUTROS AUXÍLIOS FINANCEIROS A PESSOAS FÍSICAS', 'Código da Dotação' => '1']),
            $this->csvRow(['Elemento de Despesa' => '336041 - CONTRIBUIÇÕES ', 'Código da Dotação' => '2']),
            $this->csvRow(['Elemento de Despesa' => '335041 - CONTRIBUIÇÕES ', 'Código da Dotação' => '3']),
        ]);

        $natures = BudgetAllocation::query()->orderBy('allocation_code')->pluck('expense_nature')->all();

        $this->assertSame([
            ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA,
            ExpenseNature::CONTRIBUICOES_COM_FINS_LUCRATIVOS,
            ExpenseNature::CONTRIBUICOES_SEM_FINS_LUCRATIVOS,
        ], $natures);
    }

    public function test_it_imports_nothing_when_a_row_has_an_unknown_expense_nature(): void
    {
        $this->actingAs(User::factory()->create());
        $notice = Notice::factory()->create();
        $existing = BudgetAllocation::factory()->create(['notice_id' => $notice->id]);

        try {
            $this->importRows($notice, [
                $this->csvRow(['Código da Dotação' => '1']),
                $this->csvRow(['Elemento de Despesa' => '339039 - OUTROS SERVIÇOS', 'Código da Dotação' => '2']),
                $this->csvRow(['Elemento de Despesa' => '', 'Código da Dotação' => '3']),
            ]);
            $this->fail('A importação deveria ter sido rejeitada.');
        } catch (BusinessRuleException $exception) {
            $this->assertStringContainsString('"339039 - OUTROS SERVIÇOS" (linha 3)', $exception->getMessage());
            $this->assertStringContainsString('Elemento de Despesa vazio (linha 4)', $exception->getMessage());
            $this->assertStringContainsString('nenhuma dotação foi importada', $exception->getMessage());
        }

        $this->assertDatabaseCount('budget_allocations', 1);
        $this->assertNotSoftDeleted('budget_allocations', ['id' => $existing->id]);
    }

    public function test_the_error_points_to_the_spreadsheet_line_even_after_blank_rows(): void
    {
        $this->actingAs(User::factory()->create());
        $notice = Notice::factory()->create();
        $blankRow = array_fill_keys(array_keys($this->csvRow()), '');

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('"339039 - OUTROS SERVIÇOS" (linha 5)');

        $this->importRows($notice, [
            $this->csvRow(['Código da Dotação' => '1']),
            $blankRow,
            $blankRow,
            $this->csvRow(['Elemento de Despesa' => '339039 - OUTROS SERVIÇOS', 'Código da Dotação' => '2']),
        ]);
    }

    public function test_import_and_preview_reject_a_file_without_allocation_code_or_number(): void
    {
        $user = User::factory()->create();
        SpatieRole::firstOrCreate([
            'name' => Role::BUDGETARY->value,
            'guard_name' => 'web',
        ]);
        $user->assignRole(Role::BUDGETARY->value);
        $notice = Notice::factory()->create();
        $file = fn () => $this->csvFile($this->csvRow([
            'Código da Dotação' => '',
            'Número da dotação completa' => '',
        ]));

        foreach (['budget-allocations.preview', 'budget-allocations.import'] as $route) {
            $this->actingAs($user)
                ->post(route($route, $notice), ['file' => $file()], ['Accept' => 'application/json'])
                ->assertUnprocessable()
                ->assertJsonPath('message', 'Nenhuma linha do arquivo possui código ou número de dotação.');
        }

        $this->assertDatabaseCount('budget_allocations', 0);
    }

    public function test_preview_endpoint_rejects_an_unknown_expense_nature(): void
    {
        $user = User::factory()->create();
        SpatieRole::firstOrCreate([
            'name' => Role::BUDGETARY->value,
            'guard_name' => 'web',
        ]);
        $user->assignRole(Role::BUDGETARY->value);
        $notice = Notice::factory()->create();

        $this->actingAs($user)
            ->post(route('budget-allocations.preview', $notice), [
                'file' => $this->csvFile($this->csvRow(['Elemento de Despesa' => '449052 - EQUIPAMENTOS'])),
            ], [
                'Accept' => 'application/json',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', fn (string $message) => str_contains($message, '449052 - EQUIPAMENTOS'));

        $this->assertDatabaseCount('budget_allocations', 0);
    }

    private function createBudget(): array
    {
        $notice = Notice::factory()->create();
        $project = Project::factory()->create([
            'notice_id' => $notice->id,
        ]);
        $budget = Budget::factory()->create([
            'project_id' => $project->id,
        ]);
        $installment = Installment::factory()->create([
            'budget_id' => $budget->id,
            'installment_number' => $project->current_installment_cycle ?? 1,
        ]);

        return [$notice, $budget, $installment];
    }

    private function import(Notice $notice, array $row): array
    {
        return app(BudgetAllocationImportService::class)->import(
            file: $this->csvFile($row),
            notice: $notice,
        );
    }

    private function importRows(Notice $notice, array $rows): array
    {
        return app(BudgetAllocationImportService::class)->import(
            file: $this->csvFileWithRows($rows),
            notice: $notice,
        );
    }

    private function csvFile(array $row): UploadedFile
    {
        return $this->csvFileWithRows([$row]);
    }

    private function csvFileWithRows(array $rows): UploadedFile
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, array_keys($rows[0]), ',', '"', '');

        foreach ($rows as $row) {
            fputcsv($handle, array_values($row), ',', '"', '');
        }

        rewind($handle);
        $contents = stream_get_contents($handle);
        fclose($handle);

        return UploadedFile::fake()->createWithContent('budget-allocations.csv', $contents);
    }

    private function csvRow(array $overrides = []): array
    {
        return array_replace([
            'Gestão/Unidade' => '123/456',
            'Programa de trabalho' => '131 - Promoção e Desenvolvimento da Arte',
            'Objetivo' => 'Democratizar, fomentar e ampliar o acesso',
            'Entrega' => '1894 - Projeto apoiado',
            'Função' => '13 - Cultura',
            'Subfunção' => '392 - Difusão cultural',
            'Projeto/Atividade' => '11607 - Promoção do edital',
            'Elemento de Despesa' => '339048',
            'Fonte de Recursos' => '719',
            'MAPP' => '635',
            'Projeto Finalístico' => 'Projeto finalístico',
            'Cód. da Região' => '03',
            'Macrorregião de Planejamento' => 'Cariri',
            'Código da Dotação' => '123456',
            'Número da dotação completa' => '12345678901234567890123456789012345678901',
        ], $overrides);
    }
}
