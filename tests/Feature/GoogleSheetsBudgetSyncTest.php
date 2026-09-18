<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\BudgetAllocation;
use App\Models\Installment;
use App\Models\Notice;
use App\Models\Project;
use App\Models\User;
use App\Services\GoogleSheetsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GoogleSheetsBudgetSyncTest extends TestCase
{
    use RefreshDatabase;

    private array $sheetRows = [];

    private function fakeSheet(array $rows): void
    {
        $this->sheetRows = $rows;

        Http::fake([
            'docs.google.com/*' => function () {
                $cols = [
                    ['id' => 'A', 'label' => 'CÓDIGO INSCRIÇÃO MAPAS'],
                    ['id' => 'B', 'label' => 'DATA TRAMITAÇÃO CODIP > COAFI'],
                    ['id' => 'C', 'label' => 'DATA RECEBIMENTO CODIP'],
                    ['id' => 'D', 'label' => 'CÓDIGO DA DOTAÇÃO'],
                    ['id' => 'E', 'label' => 'DOTAÇÃO ORÇAMENTÁRIA'],
                    ['id' => 'F', 'label' => 'PROJETO FINALISTICO'],
                    ['id' => 'G', 'label' => 'DATA DE SOLICITAÇÃO DA PARCELA'],
                    ['id' => 'H', 'label' => 'Nº PARCELA'],
                    ['id' => 'I', 'label' => 'OBSERVAÇÃO'],
                    ['id' => 'J', 'label' => 'VALOR DE REPASSE (PARCELA ÚNICA)'],
                    ['id' => 'K', 'label' => 'VALOR DE REPASSE (1ª PARCELA)'],
                    ['id' => 'L', 'label' => 'VALOR DE REPASSE (2ª PARCELA)'],
                    ['id' => 'M', 'label' => 'VALOR DE REPASSE (3ª PARCELA)'],
                ];

                $formattedRows = [];
                foreach ($this->sheetRows as $row) {
                    $c = [];
                    foreach ($cols as $col) {
                        $label = $col['label'];
                        $val = $row[$label] ?? null;
                        $c[] = $val !== null ? ['v' => $val] : null;
                    }
                    $formattedRows[] = ['c' => $c];
                }

                $payload = [
                    'table' => [
                        'cols' => $cols,
                        'rows' => $formattedRows,
                    ],
                ];

                return Http::response(
                    "/*O_o*/\ngoogle.visualization.Query.setResponse(".json_encode($payload).');'
                );
            },
        ]);
    }

    private function syncBudget(?User $user = null): int
    {
        $user = $user ?? User::factory()->create();

        return app(GoogleSheetsService::class)->syncBudget('sheet-id', 'Orçamento', $user->id);
    }

    #[Test]
    public function syncs_single_installment_correctly(): void
    {
        $project = Project::factory()->create(['number' => 'INSC-100']);
        $user = User::factory()->create();

        $this->fakeSheet([
            [
                'CÓDIGO INSCRIÇÃO MAPAS' => 'INSC-100',
                'DATA TRAMITAÇÃO CODIP > COAFI' => '10/05/2024',
                'DATA RECEBIMENTO CODIP' => '05/05/2024',
                'CÓDIGO DA DOTAÇÃO' => 'DOT-001',
                'DOTAÇÃO ORÇAMENTÁRIA' => '1234567890',
                'PROJETO FINALISTICO' => 'Projeto Especial',
                'DATA DE SOLICITAÇÃO DA PARCELA' => '15/05/2024',
                'Nº PARCELA' => '1',
                'OBSERVAÇÃO' => 'Parcela única paga integralmente',
                'VALOR DE REPASSE (PARCELA ÚNICA)' => 'R$ 70.000,00',
            ],
        ]);

        $count = $this->syncBudget($user);

        $this->assertSame(1, $count);

        $budget = Budget::where('project_id', $project->id)->first();
        $this->assertNotNull($budget);
        $this->assertSame('2024-05-10', $budget->processing_date_for_coafi?->format('Y-m-d'));
        $this->assertSame('2024-05-05', $budget->processing_date_for_codip?->format('Y-m-d'));

        $allocation = BudgetAllocation::where('notice_id', $project->notice_id)->first();
        $this->assertNotNull($allocation);
        $this->assertSame('DOT-001', $allocation->allocation_code);
        $this->assertSame('1234567890', $allocation->allocation_number);
        $this->assertSame('Projeto Especial', $allocation->finalistic_project);

        $this->assertCount(1, $budget->installments);
        $installment = $budget->installments->first();
        $this->assertSame(1, $installment->installment_number);
        $this->assertSame(1, $installment->notice_installment_number);
        $this->assertEquals(70000.00, $installment->amount);
        $this->assertSame('2024-05-15', $installment->request_date?->format('Y-m-d'));
        $this->assertSame('Parcela única paga integralmente', $installment->observations);
        $this->assertSame($allocation->id, $installment->budget_allocation_id);
    }

    #[Test]
    public function syncs_multiple_installments_correctly(): void
    {
        $project = Project::factory()->create(['number' => 'INSC-200']);
        $user = User::factory()->create();

        $this->fakeSheet([
            [
                'CÓDIGO INSCRIÇÃO MAPAS' => 'INSC-200',
                'DATA TRAMITAÇÃO CODIP > COAFI' => '10/05/2024',
                'DATA RECEBIMENTO CODIP' => '05/05/2024',
                'CÓDIGO DA DOTAÇÃO' => 'DOT-MULTI',
                'DOTAÇÃO ORÇAMENTÁRIA' => '9876543210',
                'PROJETO FINALISTICO' => 'Projeto Parcelado',
                'DATA DE SOLICITAÇÃO DA PARCELA' => '12/05/2024',
                'Nº PARCELA' => null,
                'OBSERVAÇÃO' => 'Observação inicial da 1ª parcela',
                'VALOR DE REPASSE (PARCELA ÚNICA)' => null,
                'VALOR DE REPASSE (1ª PARCELA)' => 'R$ 40.000,00',
                'VALOR DE REPASSE (2ª PARCELA)' => 'R$ 30.000,00',
                'VALOR DE REPASSE (3ª PARCELA)' => 'R$ 20.000,00',
            ],
        ]);

        $count = $this->syncBudget($user);

        $this->assertSame(1, $count);

        $budget = Budget::where('project_id', $project->id)->first();
        $this->assertNotNull($budget);

        $allocation = BudgetAllocation::where('notice_id', $project->notice_id)->first();
        $this->assertNotNull($allocation);

        $installments = $budget->installments()->orderBy('installment_number')->get();
        $this->assertCount(3, $installments);

        // 1ª Parcela
        $inst1 = $installments[0];
        $this->assertSame(1, $inst1->installment_number);
        $this->assertSame(1, $inst1->notice_installment_number);
        $this->assertEquals(40000.00, $inst1->amount);
        $this->assertSame('2024-05-12', $inst1->request_date?->format('Y-m-d'));
        $this->assertSame('Observação inicial da 1ª parcela', $inst1->observations);
        $this->assertSame($allocation->id, $inst1->budget_allocation_id);

        // 2ª Parcela
        $inst2 = $installments[1];
        $this->assertSame(2, $inst2->installment_number);
        $this->assertSame(2, $inst2->notice_installment_number);
        $this->assertEquals(30000.00, $inst2->amount);
        $this->assertNull($inst2->request_date);
        $this->assertNull($inst2->observations);
        $this->assertNull($inst2->budget_allocation_id);

        // 3ª Parcela
        $inst3 = $installments[2];
        $this->assertSame(3, $inst3->installment_number);
        $this->assertSame(3, $inst3->notice_installment_number);
        $this->assertEquals(20000.00, $inst3->amount);
        $this->assertNull($inst3->request_date);
        $this->assertNull($inst3->observations);
        $this->assertNull($inst3->budget_allocation_id);
    }

    #[Test]
    public function uses_budget_allocation_resolver_fallback_when_allocation_columns_empty(): void
    {
        $notice = Notice::factory()->create();
        $allocation = BudgetAllocation::factory()->create([
            'notice_id' => $notice->id,
            'allocation_code' => 'FALLBACK-DOT',
        ]);

        $project = Project::factory()->create([
            'number' => 'INSC-300',
            'notice_id' => $notice->id,
        ]);

        $this->fakeSheet([
            [
                'CÓDIGO INSCRIÇÃO MAPAS' => 'INSC-300',
                'DATA TRAMITAÇÃO CODIP > COAFI' => '01/06/2024',
                'DATA RECEBIMENTO CODIP' => '02/06/2024',
                'CÓDIGO DA DOTAÇÃO' => '',
                'DOTAÇÃO ORÇAMENTÁRIA' => '',
                'PROJETO FINALISTICO' => '',
                'DATA DE SOLICITAÇÃO DA PARCELA' => '05/06/2024',
                'Nº PARCELA' => '1',
                'OBSERVAÇÃO' => 'Sem dotação na planilha',
                'VALOR DE REPASSE (PARCELA ÚNICA)' => 'R$ 15.000,00',
            ],
        ]);

        $this->syncBudget();

        $budget = Budget::where('project_id', $project->id)->first();
        $this->assertNotNull($budget);

        $installment = $budget->installments->first();
        $this->assertNotNull($installment);
        $this->assertSame($allocation->id, $installment->budget_allocation_id);
    }

    #[Test]
    public function ensures_idempotency_on_multiple_runs(): void
    {
        $project = Project::factory()->create(['number' => 'INSC-400']);

        $sheetData = [
            [
                'CÓDIGO INSCRIÇÃO MAPAS' => 'INSC-400',
                'DATA TRAMITAÇÃO CODIP > COAFI' => '10/05/2024',
                'DATA RECEBIMENTO CODIP' => '05/05/2024',
                'CÓDIGO DA DOTAÇÃO' => 'DOT-IDEMP',
                'DOTAÇÃO ORÇAMENTÁRIA' => '112233',
                'PROJETO FINALISTICO' => 'Projeto Idempotente',
                'DATA DE SOLICITAÇÃO DA PARCELA' => '15/05/2024',
                'Nº PARCELA' => '1',
                'OBSERVAÇÃO' => 'Execução 1',
                'VALOR DE REPASSE (PARCELA ÚNICA)' => 'R$ 50.000,00',
            ],
        ];

        $this->fakeSheet($sheetData);
        $this->syncBudget();

        $this->assertSame(1, Budget::where('project_id', $project->id)->count());
        $this->assertSame(1, Installment::where('budget_id', $project->budgets->id)->count());
        $this->assertSame(1, BudgetAllocation::where('notice_id', $project->notice_id)->count());

        // Segunda execução com dados atualizados
        $sheetData[0]['OBSERVAÇÃO'] = 'Execução 2 Atualizada';
        $sheetData[0]['VALOR DE REPASSE (PARCELA ÚNICA)'] = 'R$ 55.000,00';
        $this->fakeSheet($sheetData);
        $this->syncBudget();

        $this->assertSame(1, Budget::where('project_id', $project->id)->count());
        $this->assertSame(1, Installment::where('budget_id', $project->budgets->id)->count());
        $this->assertSame(1, BudgetAllocation::where('notice_id', $project->notice_id)->count());

        $installment = $project->budgets()->first()->installments()->first();
        $this->assertEquals(55000.00, $installment->amount);
        $this->assertSame('Execução 2 Atualizada', $installment->observations);
    }

    #[Test]
    public function handles_various_date_and_money_formats(): void
    {
        $project = Project::factory()->create(['number' => 'INSC-500']);

        $this->fakeSheet([
            [
                'CÓDIGO INSCRIÇÃO MAPAS' => 'INSC-500',
                'DATA TRAMITAÇÃO CODIP > COAFI' => '2024-06-20',
                'DATA RECEBIMENTO CODIP' => '20-06-2024',
                'CÓDIGO DA DOTAÇÃO' => 'DOT-FORMATS',
                'DOTAÇÃO ORÇAMENTÁRIA' => '999999',
                'PROJETO FINALISTICO' => 'Formatos Variados',
                'DATA DE SOLICITAÇÃO DA PARCELA' => '25/06/2024',
                'Nº PARCELA' => '1',
                'OBSERVAÇÃO' => 'Teste formatos',
                'VALOR DE REPASSE (PARCELA ÚNICA)' => '1.250.500,75',
            ],
        ]);

        $this->syncBudget();

        $budget = Budget::where('project_id', $project->id)->first();
        $this->assertSame('2024-06-20', $budget->processing_date_for_coafi?->format('Y-m-d'));
        $this->assertSame('2024-06-20', $budget->processing_date_for_codip?->format('Y-m-d'));

        $installment = $budget->installments->first();
        $this->assertSame('2024-06-25', $installment->request_date?->format('Y-m-d'));
        $this->assertEquals(1250500.75, $installment->amount);
    }

    #[Test]
    public function rolls_back_row_changes_when_sync_fails(): void
    {
        $project = Project::factory()->create(['number' => 'INSC-600']);

        // Mock BudgetAllocation to throw exception during creation/saving
        BudgetAllocation::saving(function () {
            throw new \RuntimeException('Simulated database failure during allocation save');
        });

        $this->fakeSheet([
            [
                'CÓDIGO INSCRIÇÃO MAPAS' => 'INSC-600',
                'DATA TRAMITAÇÃO CODIP > COAFI' => '2024-06-20',
                'DATA RECEBIMENTO CODIP' => '2024-06-20',
                'CÓDIGO DA DOTAÇÃO' => 'FAIL-DOT',
                'DOTAÇÃO ORÇAMENTÁRIA' => '999999',
                'PROJETO FINALISTICO' => 'Falha Transação',
                'DATA DE SOLICITAÇÃO DA PARCELA' => '25/06/2024',
                'Nº PARCELA' => '1',
                'OBSERVAÇÃO' => 'Teste rollback',
                'VALOR DE REPASSE (PARCELA ÚNICA)' => '1000',
            ],
        ]);

        $count = $this->syncBudget();

        $this->assertSame(0, $count);
        $this->assertNull(Budget::where('project_id', $project->id)->first());
        $this->assertNull(BudgetAllocation::where('allocation_code', 'FAIL-DOT')->first());
    }

    #[Test]
    public function removes_obsolete_installments_when_switching_to_single_installment(): void
    {
        $project = Project::factory()->create(['number' => 'INSC-700']);

        // 1ª execução com 3 parcelas
        $this->fakeSheet([
            [
                'CÓDIGO INSCRIÇÃO MAPAS' => 'INSC-700',
                'DATA TRAMITAÇÃO CODIP > COAFI' => '2024-06-20',
                'DATA RECEBIMENTO CODIP' => '2024-06-20',
                'VALOR DE REPASSE (1ª PARCELA)' => 'R$ 30.000,00',
                'VALOR DE REPASSE (2ª PARCELA)' => 'R$ 20.000,00',
                'VALOR DE REPASSE (3ª PARCELA)' => 'R$ 10.000,00',
            ],
        ]);
        $this->syncBudget();

        $budget = $project->budgets()->first();
        $this->assertSame(3, $budget->installments()->count());

        // 2ª execução com parcela única
        $this->fakeSheet([
            [
                'CÓDIGO INSCRIÇÃO MAPAS' => 'INSC-700',
                'DATA TRAMITAÇÃO CODIP > COAFI' => '2024-06-20',
                'DATA RECEBIMENTO CODIP' => '2024-06-20',
                'VALOR DE REPASSE (PARCELA ÚNICA)' => 'R$ 60.000,00',
                'VALOR DE REPASSE (1ª PARCELA)' => null,
                'VALOR DE REPASSE (2ª PARCELA)' => null,
                'VALOR DE REPASSE (3ª PARCELA)' => null,
            ],
        ]);
        $this->syncBudget();

        $this->assertSame(1, $budget->installments()->count());
        $inst = $budget->installments()->first();
        $this->assertSame(1, $inst->installment_number);
        $this->assertEquals(60000.00, $inst->amount);
    }

    #[Test]
    public function removes_obsolete_installments_when_multiple_installments_decrease(): void
    {
        $project = Project::factory()->create(['number' => 'INSC-800']);

        // 1ª execução com 3 parcelas
        $this->fakeSheet([
            [
                'CÓDIGO INSCRIÇÃO MAPAS' => 'INSC-800',
                'DATA TRAMITAÇÃO CODIP > COAFI' => '2024-06-20',
                'DATA RECEBIMENTO CODIP' => '2024-06-20',
                'VALOR DE REPASSE (1ª PARCELA)' => 'R$ 30.000,00',
                'VALOR DE REPASSE (2ª PARCELA)' => 'R$ 20.000,00',
                'VALOR DE REPASSE (3ª PARCELA)' => 'R$ 10.000,00',
            ],
        ]);
        $this->syncBudget();

        $budget = $project->budgets()->first();
        $this->assertSame(3, $budget->installments()->count());

        // 2ª execução com apenas 2 parcelas (3ª removida)
        $this->fakeSheet([
            [
                'CÓDIGO INSCRIÇÃO MAPAS' => 'INSC-800',
                'DATA TRAMITAÇÃO CODIP > COAFI' => '2024-06-20',
                'DATA RECEBIMENTO CODIP' => '2024-06-20',
                'VALOR DE REPASSE (1ª PARCELA)' => 'R$ 35.000,00',
                'VALOR DE REPASSE (2ª PARCELA)' => 'R$ 25.000,00',
                'VALOR DE REPASSE (3ª PARCELA)' => '',
            ],
        ]);
        $this->syncBudget();

        $this->assertSame(2, $budget->installments()->count());
        $this->assertEquals([1, 2], $budget->installments()->pluck('installment_number')->toArray());
    }
}
