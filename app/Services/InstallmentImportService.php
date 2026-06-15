<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Installment;
use App\Models\Notice;
use App\Models\Project;
use App\Support\Spreadsheet\Maps\InstallmentSpreadsheetMap;
use App\Support\Spreadsheet\SpreadsheetImporter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Shared\Date;

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
            throw new \InvalidArgumentException(
                'A planilha enviada está vazia.'
            );
        }

        $projects = Project::query()
            ->where('notice_id', $notice->id)
            ->whereIn('id', $selectedProjects)
            ->with('openings')
            ->get()
            ->mapWithKeys(function ($project) {
                $openingNup = $project->openings?->first()?->opening_nup;

                if (! $openingNup) {
                    return [];
                }

                return [
                    preg_replace('/\D+/', '', (string) $openingNup) => $project,
                ];
            });

        if ($projects->isEmpty()) {
            throw new \InvalidArgumentException(
                'Nenhum dos projetos selecionados possui processo vinculado.'
            );
        }

        $matchedRows = $data
            ->map(function ($row) use ($projects) {
                $normalizedProcesso = preg_replace(
                    '/\D+/',
                    '',
                    (string) ($row['processo'] ?? '')
                );

                $project = $projects->get($normalizedProcesso);

                if (! $project) {
                    return null;
                }

                return [
                    ...$row,
                    'project_id' => $project->id,
                ];
            })
            ->filter()
            ->values();

        if ($matchedRows->isEmpty()) {
            throw new \InvalidArgumentException(
                'Nenhuma linha da planilha corresponde aos projetos selecionados.'
            );
        }

        $imported = 0;
        $skipped = 0;

        DB::transaction(function () use (
            $matchedRows,
            $installment,
            &$imported,
            &$skipped
        ) {
            foreach ($matchedRows as $row) {
                $budget = Budget::where(
                    'project_id',
                    $row['project_id']
                )->first();

                if (! $budget) {
                    $skipped++;

                    continue;
                }

                Installment::updateOrCreate(
                    [
                        'budget_id' => $budget->id,
                        'installment_number' => $installment,
                    ],
                    [
                        'amount' => $row['liquidado'] ?? 0,
                        'settlement_number' => $row['nota_de_liquidacao'] ?? null,
                        'payment_order_number' => $row['ordem_bancaria'] ?? null,
                        'payment_amount' => $row['pago'] ?? 0,
                        'commitment_date' => ! empty($row['data_n_l'])
                            ? Date::excelToDateTimeObject($row['data_n_l'])
                            : null,
                        'payment_date' => ! empty($row['data_o_b'])
                            ? Date::excelToDateTimeObject($row['data_o_b'])
                            : null,
                        'remarks' => json_encode(
                            $row,
                            JSON_UNESCAPED_UNICODE
                        ),
                    ]
                );

                $imported++;
            }
        });

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'installment' => $installment,
        ];
    }
}
