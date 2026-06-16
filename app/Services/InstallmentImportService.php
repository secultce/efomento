<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Installment;
use App\Models\Notice;
use App\Models\Project;
use App\Support\Import;
use App\Support\Spreadsheet\Maps\InstallmentSpreadsheetMap;
use App\Support\Spreadsheet\SpreadsheetImporter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class InstallmentImportService
{
    public function import(
        UploadedFile $file,
        Notice $notice,
        int $installment,
        array $selectedProjects
    ): array {
        $data = SpreadsheetImporter::import(
            file: $file,
            mapping: InstallmentSpreadsheetMap::definition(),
            required: InstallmentSpreadsheetMap::required(),
        );

        if ($data->isEmpty()) {
            throw new \InvalidArgumentException('A planilha enviada está vazia.');
        }

        $projects = Project::query()
            ->where('notice_id', $notice->id)
            ->whereIn('id', $selectedProjects)
            ->with('openings')
            ->get();

        $projectsByNup = collect();

        foreach ($projects as $project) {
            foreach ($project->openings ?? [] as $opening) {
                $normalizedNup = Import::normalizeNup($opening->opening_nup ?? null);

                if ($normalizedNup) {
                    $projectsByNup->put($normalizedNup, $project);
                }
            }
        }

        if ($projectsByNup->isEmpty()) {
            throw new \InvalidArgumentException(
                'Nenhum dos projetos selecionados possui processo vinculado na planilha.'
            );
        }

        $matchedRows = $data
            ->map(function ($row) use ($projectsByNup) {
                $row = collect($row)->toArray();

                $rowNup = $row['processo'] ?? null;
                $normalizedNup = Import::normalizeNup($rowNup);

                if (! $normalizedNup) {
                    return null;
                }

                $project = $projectsByNup->get($normalizedNup);

                if (! $project) {
                    return null;
                }

                return array_merge($row, [
                    'project_id' => $project->id,
                    'normalized_nup' => $normalizedNup,
                ]);
            })
            ->filter()
            ->values();

        if ($matchedRows->isEmpty()) {
            throw new \InvalidArgumentException(
                'Nenhuma linha da planilha corresponde aos projetos selecionados.'
            );
        }

        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use (
            $matchedRows,
            $installment,
            &$updated,
            &$skipped
        ) {
            $projectIds = $matchedRows
                ->pluck('project_id')
                ->unique()
                ->values();

            $budgetsByProjectId = Budget::query()
                ->whereIn('project_id', $projectIds)
                ->get()
                ->keyBy('project_id');

            $budgetIds = $budgetsByProjectId
                ->pluck('id')
                ->values();

            $installmentsByBudgetId = Installment::query()
                ->whereIn('budget_id', $budgetIds)
                ->where('installment_number', $installment)
                ->get()
                ->keyBy('budget_id');

            foreach ($matchedRows as $row) {
                $budget = $budgetsByProjectId->get($row['project_id']);

                if (! $budget) {
                    $skipped++;

                    continue;
                }

                $installmentModel = $installmentsByBudgetId->get($budget->id);

                if (! $installmentModel) {
                    $skipped++;

                    continue;
                }

                $installmentModel->update([
                    'settlement_date' => Import::date($row['data_nl'] ?? null)
                        ?? $installmentModel->settlement_date,

                    'settlement_number' => Import::string($row['nota_de_liquidacao'] ?? null)
                        ?? $installmentModel->settlement_number,

                    'payment_date' => Import::date($row['data_ob'] ?? null)
                        ?? $installmentModel->payment_date,

                    'payment_order_number' => Import::string($row['ordem_bancaria'] ?? null)
                        ?? $installmentModel->payment_order_number,

                    'full_source' => Import::string($row['fonte_completa'] ?? null)
                        ?? $installmentModel->full_source,

                    'expense_nature' => Import::string($row['natureza_despesa'] ?? null)
                        ?? $installmentModel->expense_nature,

                    'process_number' => Import::string($row['processo'] ?? null)
                        ?? $installmentModel->process_number,

                    'creditor' => Import::string($row['credor'] ?? null)
                        ?? $installmentModel->creditor,

                    'creditor_name' => Import::string($row['nome_credor'] ?? null)
                        ?? $installmentModel->creditor_name,

                    'retention_creditor' => Import::string($row['credor_retencao'] ?? null)
                        ?? $installmentModel->retention_creditor,

                    'origin_bank_domicile' => Import::string($row['domicilio_bancario_origem'] ?? null)
                        ?? $installmentModel->origin_bank_domicile,

                    'committed_amount' => Import::money($row['empenhado'] ?? null)
                        ?? $installmentModel->committed_amount,

                    'amount' => Import::money($row['liquidado'] ?? null)
                        ?? $installmentModel->amount,

                    'payment_amount' => Import::money($row['pago'] ?? null)
                        ?? $installmentModel->payment_amount,
                ]);

                $updated++;
            }
        });

        return [
            'imported' => $updated,
            'updated' => $updated,
            'skipped' => $skipped,
            'installment' => $installment,
        ];
    }
}
