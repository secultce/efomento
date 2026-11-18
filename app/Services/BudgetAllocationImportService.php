<?php

namespace App\Services;

use App\Exceptions\Domain\BusinessRuleException;
use App\Models\BudgetAllocation;
use App\Models\Notice;
use App\Support\Import;
use App\Support\Spreadsheet\Maps\BudgetAllocationSpreadsheetMap;
use App\Support\Spreadsheet\SpreadsheetImporter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BudgetAllocationImportService
{
    private const MAX_PREVIEW_ROWS = 500;

    public function preview(UploadedFile $file, Notice $notice): array
    {
        return [
            'columns' => $this->columns(),
            'rows' => $this->readRows($file)
                ->take(self::MAX_PREVIEW_ROWS)
                ->values()
                ->all(),
            'has_existing_allocations' => $notice->budgetAllocations()->exists(),
        ];
    }

    public function import(UploadedFile $file, Notice $notice): array
    {
        $rows = $this->readRows($file);
        $validRows = $rows->filter(
            fn (array $row) => $row['allocation_code'] || $row['allocation_number']
        );

        if ($validRows->isEmpty()) {
            throw new BusinessRuleException(
                'Nenhuma linha do arquivo possui código ou número de dotação.'
            );
        }

        $summary = [
            'processed' => $rows->count(),
            'created' => 0,
            'updated' => 0,
            'skipped' => $rows->count() - $validRows->count(),
        ];

        DB::transaction(function () use (
            $validRows,
            $notice,
            &$summary
        ) {
            Notice::query()
                ->whereKey($notice->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $currentAllocations = BudgetAllocation::query()
                ->where('notice_id', $notice->id)
                ->lockForUpdate()
                ->get();

            $isUpdate = $currentAllocations->isNotEmpty();

            foreach ($currentAllocations as $currentAllocation) {
                $currentAllocation->delete();
            }

            foreach ($validRows as $row) {
                BudgetAllocation::create([
                    'notice_id' => $notice->id,
                    ...$row,
                ]);

                $isUpdate
                    ? $summary['updated']++
                    : $summary['created']++;
            }
        });

        return [
            'columns' => $this->columns(),
            'rows' => $rows->all(),
            'summary' => $summary,
        ];
    }

    private function readRows(UploadedFile $file): Collection
    {
        $data = SpreadsheetImporter::import(
            file: $file,
            mapping: BudgetAllocationSpreadsheetMap::definition(),
            required: BudgetAllocationSpreadsheetMap::required(),
            headerRow: 0,
            dataStartsAt: 1,
        );

        if ($data->isEmpty()) {
            throw new BusinessRuleException('O arquivo CSV enviado está vazio.');
        }

        return $this->normalizeRows($data);
    }

    private function columns(): array
    {
        return collect(BudgetAllocationSpreadsheetMap::labels())
            ->map(fn ($label, $key) => [
                'key' => $key,
                'label' => $label,
            ])
            ->values()
            ->all();
    }

    private function normalizeRows(Collection $data): Collection
    {
        $columns = array_keys(BudgetAllocationSpreadsheetMap::labels());

        return $data->map(function (array $row) use ($columns) {
            return collect($columns)
                ->mapWithKeys(fn ($column) => [
                    $column => Import::string($row[$column] ?? null),
                ])
                ->all();
        });
    }
}
