<?php

namespace Tests\Feature;

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

    public function test_it_updates_existing_installment_using_processo_and_installment_number(): void
    {
        $notice = Notice::factory()->create();

        $project = Project::factory()->create([
            'notice_id' => $notice->id,
        ]);

        $project->openings()->create([
            'opening_nup' => '23000.000001/2024-10',
        ]);

        $budget = Budget::factory()->create([
            'project_id' => $project->id,
        ]);

        $installment = Installment::factory()->create([
            'budget_id' => $budget->id,
            'installment_number' => 1,
            'amount' => 0,
            'payment_amount' => null,
            'request_date' => '2026-01-01',
            'remarks' => 'Observação manual que não deve mudar',
        ]);

        $file = $this->makeSpreadsheet([
            [
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
            ],
        ]);

        $result = app(InstallmentImportService::class)->import(
            file: $file,
            notice: $notice,
            installment: 1,
            selectedProjects: [$project->id],
        );

        $this->assertSame(1, $result['updated']);
        $this->assertSame(1, $result['imported']);
        $this->assertSame(0, $result['skipped']);
        $this->assertSame(1, $result['installment']);

        $installment->refresh();

        $this->assertSame('NL123', $installment->settlement_number);
        $this->assertSame('2026-01-10', $installment->settlement_date->format('Y-m-d'));
        $this->assertSame('900,25', $installment->settlement_amount);

        $this->assertSame('OB456', $installment->payment_order_number);
        $this->assertSame('2026-01-15', $installment->payment_date->format('Y-m-d'));

        $this->assertSame('Fonte Teste', $installment->full_source);
        $this->assertSame('339030', $installment->expense_nature);
        $this->assertSame('23000.000001/2024-10', $installment->process_number);

        $this->assertSame('12345678900', $installment->creditor);
        $this->assertSame('Credor Teste', $installment->creditor_name);
        $this->assertSame('Retenção Teste', $installment->retention_creditor);
        $this->assertSame('Banco Teste', $installment->origin_bank_domicile);

        $this->assertSame('1000.50', $installment->committed_amount);
        $this->assertSame('850.10', $installment->payment_amount);

        $this->assertSame('Observação manual que não deve mudar', $installment->remarks);
    }

    public function test_it_does_not_create_installment_when_installment_does_not_exist(): void
    {
        $notice = Notice::factory()->create();

        $project = Project::factory()->create([
            'notice_id' => $notice->id,
        ]);

        $project->openings()->create([
            'opening_nup' => '23000.000001/2024-10',
        ]);

        Budget::factory()->create([
            'project_id' => $project->id,
        ]);

        $file = $this->makeSpreadsheet([
            [
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
            ],
        ]);

        $result = app(InstallmentImportService::class)->import(
            file: $file,
            notice: $notice,
            installment: 1,
            selectedProjects: [$project->id],
        );

        $this->assertSame(0, $result['updated']);
        $this->assertSame(0, $result['imported']);
        $this->assertSame(1, $result['skipped']);

        $this->assertDatabaseMissing('installments', [
            'installment_number' => 1,
            'settlement_number' => 'NL123',
        ]);
    }

    public function test_it_skips_when_project_has_no_budget(): void
    {
        $notice = Notice::factory()->create();

        $project = Project::factory()->create([
            'notice_id' => $notice->id,
        ]);

        $project->openings()->create([
            'opening_nup' => '23000.000001/2024-10',
        ]);

        $file = $this->makeSpreadsheet([
            [
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
            ],
        ]);

        $result = app(InstallmentImportService::class)->import(
            file: $file,
            notice: $notice,
            installment: 1,
            selectedProjects: [$project->id],
        );

        $this->assertSame(0, $result['updated']);
        $this->assertSame(0, $result['imported']);
        $this->assertSame(1, $result['skipped']);
    }

    public function test_it_throws_when_no_spreadsheet_rows_match_selected_projects(): void
    {
        $notice = Notice::factory()->create();

        $project = Project::factory()->create([
            'notice_id' => $notice->id,
        ]);

        $project->openings()->create([
            'opening_nup' => '23000.000001/2024-10',
        ]);

        $file = $this->makeSpreadsheet([
            [
                'Data NL' => '10/01/2026',
                'Nota de Liquidação' => 'NL123',
                'Data OB' => '15/01/2026',
                'Ordem Bancária' => 'OB456',
                'Fonte Completa' => 'Fonte Teste',
                'Natureza Despesa' => '339030',
                'Processo' => '99999.999999/2024-99',
                'Credor' => '12345678900',
                'Nome do Credor' => 'Credor Teste',
                'Credor da Retenção' => 'Retenção Teste',
                'Domicílio Bancário Origem (OB)' => 'Banco Teste',
                'Empenhado' => '1.000,50',
                'Liquidado' => '900,25',
                'Pago' => '850,10',
            ],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Nenhuma linha da planilha corresponde aos projetos selecionados.'
        );

        app(InstallmentImportService::class)->import(
            file: $file,
            notice: $notice,
            installment: 1,
            selectedProjects: [$project->id],
        );
    }

    public function test_it_throws_when_selected_projects_have_no_opening_nup(): void
    {
        $notice = Notice::factory()->create();

        $project = Project::factory()->create([
            'notice_id' => $notice->id,
        ]);

        $file = $this->makeSpreadsheet([
            [
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
            ],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Nenhum dos projetos selecionados possui processo vinculado na planilha.'
        );

        app(InstallmentImportService::class)->import(
            file: $file,
            notice: $notice,
            installment: 1,
            selectedProjects: [$project->id],
        );
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

        $sheet->fromArray([$headers], null, 'A2');

        foreach ($rows as $index => $row) {
            $line = [];

            foreach ($headers as $header) {
                $line[] = $row[$header] ?? null;
            }

            $sheet->fromArray([$line], null, 'A'.($index + 3));
        }

        $path = storage_path('app/testing/installments-'.Str::uuid().'.xlsx');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
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
