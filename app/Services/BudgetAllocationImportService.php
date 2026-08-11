<?php

namespace App\Services;

use App\Exceptions\Domain\BusinessRuleException;
use App\Models\Budget;
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
    public function preview(UploadedFile $file, Notice $notice): array
    {
        return [
            'columns' => $this->columns(),
            'rows' => $this->readRows($file)->all(),
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

        $budgets = Budget::query()
            ->with('project.opening')
            ->whereHas('project', fn ($query) => $query->where('notice_id', $notice->id))
            ->whereHas('project.opening')
            ->get();

        $budgetsByAllocationNumber = $this->groupBudgetsBy($budgets, 'allocation_number');
        $budgetsByAllocationCode = $this->groupBudgetsBy($budgets, 'allocation_code');

        $summary = [
            'processed' => $rows->count(),
            'matched' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => $rows->count() - $validRows->count(),
            'without_budget' => 0,
        ];

        DB::transaction(function () use (
            $validRows,
            $budgetsByAllocationNumber,
            $budgetsByAllocationCode,
            $notice,
            &$summary
        ) {
            $currentAllocations = BudgetAllocation::query()
                ->where('notice_id', $notice->id)
                ->with('budgets')
                ->lockForUpdate()
                ->get();

            $isUpdate = $currentAllocations->isNotEmpty();

            foreach ($currentAllocations as $currentAllocation) {
                foreach ($currentAllocation->budgets as $budget) {
                    $budget->budgetAllocation()->dissociate();
                    $budget->save();
                }

                $currentAllocation->delete();
            }

            foreach ($validRows as $row) {
                $matchingBudget = $this->findBudgets(
                    row: $row,
                    budgetsByAllocationNumber: $budgetsByAllocationNumber,
                    budgetsByAllocationCode: $budgetsByAllocationCode,
                )->first();

                $matchingBudget
                    ? $summary['matched']++
                    : $summary['without_budget']++;

                $allocation = BudgetAllocation::create([
                    'notice_id' => $notice->id,
                    ...$row,
                ]);

                if ($matchingBudget) {
                    $matchingBudget->budgetAllocation()->associate($allocation);
                    $matchingBudget->save();
                }

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

    private function groupBudgetsBy(Collection $budgets, string $openingField): Collection
    {
        return $budgets
            ->filter(fn (Budget $budget) => filled($budget->project?->opening?->{$openingField}))
            ->groupBy(fn (Budget $budget) => $this->normalizeAllocationIdentifier(
                $budget->project->opening->{$openingField}
            ));
    }

    private function findBudgets(
        array $row,
        Collection $budgetsByAllocationNumber,
        Collection $budgetsByAllocationCode
    ): Collection {
        $allocationNumber = $this->normalizeAllocationIdentifier($row['allocation_number']);

        if ($allocationNumber && $budgetsByAllocationNumber->has($allocationNumber)) {
            return $budgetsByAllocationNumber->get($allocationNumber);
        }

        $allocationCode = $this->normalizeAllocationIdentifier($row['allocation_code']);

        if ($allocationCode && $budgetsByAllocationCode->has($allocationCode)) {
            return $budgetsByAllocationCode->get($allocationCode);
        }

        return collect();
    }

    private function normalizeAllocationIdentifier(mixed $value): ?string
    {
        $value = Import::string($value);

        if (! $value) {
            return null;
        }

        $normalized = preg_replace('/[^a-zA-Z0-9]+/', '', $value);

        return $normalized !== '' ? strtolower($normalized) : null;
    }
}
