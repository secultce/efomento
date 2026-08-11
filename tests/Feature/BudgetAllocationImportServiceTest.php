<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Budget;
use App\Models\BudgetAllocation;
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

        [$notice, $budget] = $this->createBudget('123456', '12345678901234567890123456789012345678901');

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
            'budget_allocation_id' => null,
        ]);
    }

    public function test_it_updates_an_existing_allocation_when_the_same_csv_is_uploaded_again(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        [$notice] = $this->createBudget('123456', '12345678901234567890123456789012345678901');

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
        [$notice] = $this->createBudget('123456', '12345678901234567890123456789012345678901');

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
        [$notice] = $this->createBudget('123456', '12345678901234567890123456789012345678901');

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

    private function createBudget(string $allocationCode, string $allocationNumber): array
    {
        $notice = Notice::factory()->create();
        $project = Project::factory()->create([
            'notice_id' => $notice->id,
        ]);
        $project->opening->update([
            'allocation_code' => $allocationCode,
            'allocation_number' => $allocationNumber,
        ]);
        $budget = Budget::factory()->create([
            'project_id' => $project->id,
        ]);

        return [$notice, $budget];
    }

    private function import(Notice $notice, array $row): array
    {
        return app(BudgetAllocationImportService::class)->import(
            file: $this->csvFile($row),
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
