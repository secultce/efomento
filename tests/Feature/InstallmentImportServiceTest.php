<?php

namespace Tests\Feature;

use App\Exceptions\Domain\BusinessRuleException;
use App\Models\Budget;
use App\Models\Installment;
use App\Models\Notice;
use App\Models\Project;
use App\Services\InstallmentImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class InstallmentImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_first_pending_installment_using_processo(): void
    {
        $notice = Notice::factory()->create();

        $project = Project::factory()->create([
            'notice_id' => $notice->id,
        ]);

        $project->opening->update([
            'opening_nup' => '23000.000001/2024-10',
        ]);

        $budget = Budget::factory()->create([
            'project_id' => $project->id,
        ]);

        $installment = Installment::factory()->create([
            'budget_id' => $budget->id,
            'installment_number' => 1,
            'amount' => 0,
            'committed_amount' => null,
            'settlement_amount' => null,
            'payment_amount' => null,
            'request_date' => '2026-01-01',
            'remarks' => 'Observação manual que não deve mudar',
        ]);

        $file = $this->makeSpreadsheet([
            $this->paymentRow(),
        ]);

        $result = $this->importSpreadsheet(
            $file,
            [$project->id],
        );

        $this->assertSame(1, $result['processed']);
        $this->assertSame(1, $result['matched']);
        $this->assertSame(1, $result['updated']);
        $this->assertSame(1, $result['imported']);
        $this->assertSame(0, $result['skipped']);

        $this->assertSame(1, $result['pending_updated']);
        $this->assertSame(0, $result['irregular_updated']);
        $this->assertSame(1, $result['irregular_after_update']);

        $this->assertSame([1], $result['installments']);

        $installment->refresh();

        $this->assertSame(
            'NL123',
            $installment->settlement_number,
        );

        $this->assertSame(
            '2026-01-10',
            $installment->settlement_date->format('Y-m-d'),
        );

        $this->assertSame(
            '900.25',
            $installment->settlement_amount,
        );

        $this->assertSame(
            'OB456',
            $installment->payment_order_number,
        );

        $this->assertSame(
            '2026-01-15',
            $installment->payment_date->format('Y-m-d'),
        );

        $this->assertSame(
            'Fonte Teste',
            $installment->full_source,
        );

        $this->assertSame(
            '339030',
            $installment->expense_nature,
        );

        $this->assertSame(
            '23000.000001/2024-10',
            $installment->process_number,
        );

        $this->assertSame(
            '12345678900',
            $installment->creditor,
        );

        $this->assertSame(
            'Credor Teste',
            $installment->creditor_name,
        );

        $this->assertSame(
            'Retenção Teste',
            $installment->retention_creditor,
        );

        $this->assertSame(
            'Banco Teste',
            $installment->origin_bank_domicile,
        );

        $this->assertSame(
            '1000.50',
            $installment->committed_amount,
        );

        $this->assertSame(
            '850.10',
            $installment->payment_amount,
        );

        $this->assertSame(
            'Observação manual que não deve mudar',
            $installment->remarks,
        );
    }

    public function test_it_updates_next_pending_installment_when_previous_one_is_paid_and_regular(): void
    {
        $notice = Notice::factory()->create();

        $project = Project::factory()->create([
            'notice_id' => $notice->id,
        ]);

        $project->opening->update([
            'opening_nup' => '23000.000001/2024-10',
        ]);

        $budget = Budget::factory()->create([
            'project_id' => $project->id,
        ]);

        $firstInstallment = Installment::factory()->create([
            'budget_id' => $budget->id,
            'installment_number' => 1,
            'amount' => 100,
            'committed_amount' => 100,
            'settlement_amount' => 100,
            'payment_amount' => 100,
            'settlement_date' => '2026-01-10',
            'settlement_number' => 'OLD-NL',
            'payment_date' => '2026-01-15',
            'payment_order_number' => 'OLD-OB',
            'request_date' => '2026-01-01',
        ]);

        $secondInstallment = Installment::factory()->create([
            'budget_id' => $budget->id,
            'installment_number' => 2,
            'amount' => 0,
            'committed_amount' => null,
            'settlement_amount' => null,
            'payment_amount' => null,
            'request_date' => '2026-02-01',
        ]);

        $file = $this->makeSpreadsheet([
            $this->paymentRow([
                'Data NL' => '10/02/2026',
                'Nota de Liquidação' => 'NEW-NL',
                'Data OB' => '15/02/2026',
                'Ordem Bancária' => 'NEW-OB',
            ]),
        ]);

        $result = $this->importSpreadsheet(
            $file,
            [$project->id],
        );

        $this->assertSame(1, $result['updated']);
        $this->assertSame(1, $result['imported']);
        $this->assertSame(0, $result['skipped']);
        $this->assertSame(1, $result['pending_updated']);
        $this->assertSame([2], $result['installments']);

        $firstInstallment->refresh();
        $secondInstallment->refresh();

        $this->assertSame(
            'OLD-NL',
            $firstInstallment->settlement_number,
        );

        $this->assertSame(
            'OLD-OB',
            $firstInstallment->payment_order_number,
        );

        $this->assertSame(
            '100.00',
            $firstInstallment->payment_amount,
        );

        $this->assertSame(
            'NEW-NL',
            $secondInstallment->settlement_number,
        );

        $this->assertSame(
            '2026-02-10',
            $secondInstallment->settlement_date->format('Y-m-d'),
        );

        $this->assertSame(
            'NEW-OB',
            $secondInstallment->payment_order_number,
        );

        $this->assertSame(
            '2026-02-15',
            $secondInstallment->payment_date->format('Y-m-d'),
        );

        $this->assertSame(
            '1000.50',
            $secondInstallment->committed_amount,
        );

        $this->assertSame(
            '900.25',
            $secondInstallment->settlement_amount,
        );

        $this->assertSame(
            '850.10',
            $secondInstallment->payment_amount,
        );
    }

    public function test_it_updates_irregular_installment_when_payment_date_matches(): void
    {
        $notice = Notice::factory()->create();

        $project = Project::factory()->create([
            'notice_id' => $notice->id,
        ]);

        $project->opening->update([
            'opening_nup' => '23000.000001/2024-10',
        ]);

        $budget = Budget::factory()->create([
            'project_id' => $project->id,
        ]);

        $irregularInstallment = Installment::factory()->create([
            'budget_id' => $budget->id,
            'installment_number' => 1,
            'amount' => 1000.50,
            'committed_amount' => 1000.50,
            'settlement_amount' => 900.25,
            'payment_amount' => 850.10,
            'settlement_date' => '2026-01-10',
            'settlement_number' => 'OLD-NL',
            'payment_date' => '2026-01-15',
            'payment_order_number' => 'OLD-OB',
            'request_date' => '2026-01-01',
        ]);

        $secondInstallment = Installment::factory()->create([
            'budget_id' => $budget->id,
            'installment_number' => 2,
            'amount' => 0,
            'committed_amount' => null,
            'settlement_amount' => null,
            'payment_amount' => null,
            'request_date' => '2026-02-01',
        ]);

        $file = $this->makeSpreadsheet([
            $this->paymentRow([
                'Data OB' => '15/01/2026',
                'Nota de Liquidação' => 'CORRECTED-NL',
                'Ordem Bancária' => 'CORRECTED-OB',
            ]),
        ]);

        $result = $this->importSpreadsheet(
            $file,
            [$project->id],
        );

        $this->assertSame(1, $result['updated']);
        $this->assertSame(1, $result['imported']);
        $this->assertSame(0, $result['skipped']);

        $this->assertSame(0, $result['pending_updated']);
        $this->assertSame(1, $result['irregular_updated']);
        $this->assertSame(1, $result['irregular_after_update']);

        $this->assertSame([1], $result['installments']);

        $irregularInstallment->refresh();
        $secondInstallment->refresh();

        $this->assertSame(
            'CORRECTED-NL',
            $irregularInstallment->settlement_number,
        );

        $this->assertSame(
            'CORRECTED-OB',
            $irregularInstallment->payment_order_number,
        );

        $this->assertSame(
            '2026-01-15',
            $irregularInstallment->payment_date->format('Y-m-d'),
        );

        $this->assertNull($secondInstallment->payment_date);
        $this->assertNull($secondInstallment->payment_amount);
    }

    public function test_it_updates_pending_installment_when_payment_date_matches(): void
    {
        $notice = Notice::factory()->create();

        $project = Project::factory()->create([
            'notice_id' => $notice->id,
        ]);

        $project->opening->update([
            'opening_nup' => '23000.000001/2024-10',
        ]);

        $budget = Budget::factory()->create([
            'project_id' => $project->id,
        ]);

        $pendingInstallment = Installment::factory()->create([
            'budget_id' => $budget->id,
            'installment_number' => 1,
            'amount' => 1000.50,
            'committed_amount' => null,
            'settlement_amount' => null,
            'payment_amount' => null,
            'payment_date' => '2026-01-15',
            'request_date' => '2026-01-01',
        ]);

        $secondInstallment = Installment::factory()->create([
            'budget_id' => $budget->id,
            'installment_number' => 2,
            'amount' => 0,
            'committed_amount' => null,
            'settlement_amount' => null,
            'payment_amount' => null,
            'request_date' => '2026-02-01',
        ]);

        $file = $this->makeSpreadsheet([
            $this->paymentRow([
                'Data OB' => '15/01/2026',
            ]),
        ]);

        $result = $this->importSpreadsheet(
            $file,
            [$project->id],
        );

        $this->assertSame(1, $result['updated']);
        $this->assertSame(1, $result['pending_updated']);
        $this->assertSame(0, $result['irregular_updated']);
        $this->assertSame([1], $result['installments']);

        $pendingInstallment->refresh();
        $secondInstallment->refresh();

        $this->assertSame(
            '1000.50',
            $pendingInstallment->committed_amount,
        );

        $this->assertSame(
            '900.25',
            $pendingInstallment->settlement_amount,
        );

        $this->assertSame(
            '850.10',
            $pendingInstallment->payment_amount,
        );

        $this->assertNull($secondInstallment->payment_date);
        $this->assertNull($secondInstallment->payment_amount);
    }

    public function test_it_skips_matching_payment_date_when_installment_is_paid_and_regular(): void
    {
        $notice = Notice::factory()->create();

        $project = Project::factory()->create([
            'notice_id' => $notice->id,
        ]);

        $project->opening->update([
            'opening_nup' => '23000.000001/2024-10',
        ]);

        $budget = Budget::factory()->create([
            'project_id' => $project->id,
        ]);

        $installment = Installment::factory()->create([
            'budget_id' => $budget->id,
            'installment_number' => 1,
            'amount' => 1000.50,
            'committed_amount' => 1000.50,
            'settlement_amount' => 1000.50,
            'payment_amount' => 1000.50,
            'settlement_date' => '2026-01-10',
            'settlement_number' => 'PAID-NL',
            'payment_date' => '2026-01-15',
            'payment_order_number' => 'PAID-OB',
            'request_date' => '2026-01-01',
        ]);

        $file = $this->makeSpreadsheet([
            $this->paymentRow([
                'Data OB' => '15/01/2026',
                'Nota de Liquidação' => 'SHOULD-NOT-UPDATE',
                'Ordem Bancária' => 'SHOULD-NOT-UPDATE',
            ]),
        ]);

        $result = $this->importSpreadsheet(
            $file,
            [$project->id],
        );

        $this->assertSame(0, $result['updated']);
        $this->assertSame(0, $result['imported']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame(1, $result['already_paid_regular']);
        $this->assertSame([], $result['installments']);

        $installment->refresh();

        $this->assertSame(
            'PAID-NL',
            $installment->settlement_number,
        );

        $this->assertSame(
            'PAID-OB',
            $installment->payment_order_number,
        );

        $this->assertSame(
            'Já existe uma parcela paga e regular com essa data de pagamento.',
            $result['ignored'][0]['reason'],
        );
    }

    public function test_it_skips_when_payment_date_breaks_installment_order(): void
    {
        $notice = Notice::factory()->create();

        $project = Project::factory()->create([
            'notice_id' => $notice->id,
        ]);

        $project->opening->update([
            'opening_nup' => '23000.000001/2024-10',
        ]);

        $budget = Budget::factory()->create([
            'project_id' => $project->id,
        ]);

        Installment::factory()->create([
            'budget_id' => $budget->id,
            'installment_number' => 1,
            'amount' => 100,
            'committed_amount' => 100,
            'settlement_amount' => 100,
            'payment_amount' => 100,
            'settlement_date' => '2026-01-15',
            'settlement_number' => 'FIRST-NL',
            'payment_date' => '2026-01-20',
            'payment_order_number' => 'FIRST-OB',
            'request_date' => '2026-01-01',
        ]);

        $secondInstallment = Installment::factory()->create([
            'budget_id' => $budget->id,
            'installment_number' => 2,
            'amount' => 0,
            'committed_amount' => null,
            'settlement_amount' => null,
            'payment_amount' => null,
            'request_date' => '2026-02-01',
        ]);

        $file = $this->makeSpreadsheet([
            $this->paymentRow([
                'Data NL' => '10/01/2026',
                'Data OB' => '15/01/2026',
            ]),
        ]);

        $result = $this->importSpreadsheet(
            $file,
            [$project->id],
        );

        $this->assertSame(0, $result['updated']);
        $this->assertSame(0, $result['imported']);
        $this->assertSame(1, $result['skipped']);

        $this->assertSame(
            1,
            $result['invalid_chronological_dates'],
        );

        $this->assertSame([], $result['installments']);

        $this->assertSame(
            'Data de pagamento viola a ordem cronológica das parcelas.',
            $result['ignored'][0]['reason'],
        );

        $secondInstallment->refresh();

        $this->assertNull($secondInstallment->payment_date);
        $this->assertNull($secondInstallment->payment_amount);
    }

    public function test_it_does_not_create_installment_when_budget_has_no_installments(): void
    {
        $notice = Notice::factory()->create();

        $project = Project::factory()->create([
            'notice_id' => $notice->id,
        ]);

        $project->opening->update([
            'opening_nup' => '23000.000001/2024-10',
        ]);

        Budget::factory()->create([
            'project_id' => $project->id,
        ]);

        $file = $this->makeSpreadsheet([
            $this->paymentRow(),
        ]);

        $result = $this->importSpreadsheet(
            $file,
            [$project->id],
        );

        $this->assertSame(1, $result['matched']);
        $this->assertSame(0, $result['updated']);
        $this->assertSame(0, $result['imported']);
        $this->assertSame(1, $result['skipped']);

        $this->assertSame(
            1,
            $result['installments_not_found'],
        );

        $this->assertSame([], $result['installments']);

        $this->assertSame(
            'Orçamento não possui parcelas cadastradas.',
            $result['ignored'][0]['reason'],
        );

        $this->assertDatabaseMissing('installments', [
            'settlement_number' => 'NL123',
        ]);
    }

    public function test_it_skips_when_project_has_no_budget(): void
    {
        $notice = Notice::factory()->create();

        $project = Project::factory()->create([
            'notice_id' => $notice->id,
        ]);

        $project->opening->update([
            'opening_nup' => '23000.000001/2024-10',
        ]);

        $file = $this->makeSpreadsheet([
            $this->paymentRow(),
        ]);

        $result = $this->importSpreadsheet(
            $file,
            [$project->id],
        );

        $this->assertSame(1, $result['matched']);
        $this->assertSame(0, $result['updated']);
        $this->assertSame(0, $result['imported']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame(1, $result['budgets_not_found']);
        $this->assertSame([], $result['installments']);

        $this->assertSame(
            'Projeto não possui orçamento cadastrado.',
            $result['ignored'][0]['reason'],
        );
    }

    public function test_it_skips_when_spreadsheet_nup_does_not_match_selected_project(): void
    {
        $notice = Notice::factory()->create();

        $project = Project::factory()->create([
            'notice_id' => $notice->id,
        ]);

        $project->opening->update([
            'opening_nup' => '23000.000001/2024-10',
        ]);

        $file = $this->makeSpreadsheet([
            $this->paymentRow([
                'Processo' => '99999.999999/2024-99',
            ]),
        ]);

        $result = $this->importSpreadsheet(
            $file,
            [$project->id],
        );

        $this->assertSame(1, $result['processed']);
        $this->assertSame(0, $result['matched']);
        $this->assertSame(0, $result['updated']);
        $this->assertSame(0, $result['imported']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame(1, $result['nups_not_found']);
        $this->assertSame([], $result['installments']);

        $this->assertSame(
            'NUP não encontrado entre os projetos disponíveis.',
            $result['ignored'][0]['reason'],
        );
    }

    public function test_it_throws_when_selected_project_has_no_valid_opening_nup(): void
    {
        $notice = Notice::factory()->create();

        $project = Project::factory()->create([
            'notice_id' => $notice->id,
        ]);

        $project->opening->update([
            'opening_nup' => null,
        ]);

        $file = $this->makeSpreadsheet([
            $this->paymentRow(),
        ]);

        $this->expectException(
            BusinessRuleException::class,
        );

        $this->expectExceptionMessage(
            'Nenhum projeto possui processo vinculado para comparação com a planilha.',
        );

        $this->importSpreadsheet(
            $file,
            [$project->id],
        );
    }

    private function importSpreadsheet(
        UploadedFile $file,
        array $selectedProjectIds,
    ): array {
        return app(InstallmentImportService::class)->import(
            $file,
            $selectedProjectIds,
        );
    }

    private function paymentRow(array $overrides = []): array
    {
        return array_replace([
            'Data NL' => '10/01/2026',
            'Nota de Liquidação' => 'NL123',
            'Data OB' => '15/01/2026',
            'Ordem Bancária' => 'OB456',
            'Fonte Completa' => 'Fonte Teste',
            'Natureza Despesa' => '339030',
            'Processo' => '23000.000001/2024-10',
            'Credor' => '12345678900',
            'Nome do Credor' => 'Credor Teste',
            'Credor da Retenção' => 'Retenção Teste',
            'Domicílio Bancário Origem (OB)' => 'Banco Teste',
            'Empenhado' => '1.000,50',
            'Liquidado' => '900,25',
            'Pago' => '850,10',
        ], $overrides);
    }

    private function makeSpreadsheet(array $rows): UploadedFile
    {
        $headers = [
            'Data NL',
            'Nota de Liquidação',
            'Data OB',
            'Ordem Bancária',
            'Fonte Completa',
            'Natureza Despesa',
            'Processo',
            'Credor',
            'Nome do Credor',
            'Credor da Retenção',
            'Domicílio Bancário Origem (OB)',
            'Empenhado',
            'Liquidado',
            'Pago',
        ];

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->fromArray(
            [$headers],
            null,
            'A2',
        );

        foreach ($rows as $index => $row) {
            $line = [];

            foreach ($headers as $header) {
                $line[] = $row[$header] ?? null;
            }

            $sheet->fromArray(
                [$line],
                null,
                'A'.($index + 3),
            );
        }

        $path = storage_path(
            'app/testing/installments-'.Str::uuid().'.xlsx',
        );

        if (! is_dir(dirname($path))) {
            mkdir(
                dirname($path),
                0777,
                true,
            );
        }

        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile(
            path: $path,
            originalName: 'installments.xlsx',
            mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            error: null,
            test: true,
        );
    }
}
