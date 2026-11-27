<?php

namespace App\Services;

use App\Enums\ExpenseNature;
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
        $rows = $this->readRows($file);

        $this->resolveExpenseNatures($this->allocationRows($rows));

        return [
            'columns' => $this->columns(),
            'rows' => $rows
                ->take(self::MAX_PREVIEW_ROWS)
                ->values()
                ->all(),
            'has_existing_allocations' => $notice->budgetAllocations()->exists(),
        ];
    }

    public function import(UploadedFile $file, Notice $notice): array
    {
        $rows = $this->readRows($file);
        $validRows = $this->allocationRows($rows);
        $expenseNatures = $this->resolveExpenseNatures($validRows);

        $summary = [
            'processed' => $rows->count(),
            'created' => 0,
            'updated' => 0,
            'skipped' => $rows->count() - $validRows->count(),
        ];

        DB::transaction(function () use (
            $validRows,
            $expenseNatures,
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

            foreach ($validRows as $line => $row) {
                BudgetAllocation::create([
                    'notice_id' => $notice->id,
                    ...$row,
                    'expense_nature' => $expenseNatures[$line],
                ]);

                $isUpdate
                    ? $summary['updated']++
                    : $summary['created']++;
            }
        });

        return [
            'columns' => $this->columns(),
            'rows' => $rows->values()->all(),
            'summary' => $summary,
        ];
    }

    /**
     * Linhas que representam uma dotação (possuem código ou número), mantendo o
     * índice original para reportar a linha da planilha em caso de erro.
     */
    private function allocationRows(Collection $rows): Collection
    {
        $validRows = $rows->filter(
            fn (array $row) => $row['allocation_code'] || $row['allocation_number']
        );

        if ($validRows->isEmpty()) {
            throw new BusinessRuleException(
                'Nenhuma linha do arquivo possui código ou número de dotação.'
            );
        }

        return $validRows;
    }

    /**
     * Converte o "Elemento de Despesa" de cada linha em ExpenseNature. Se alguma
     * linha tiver natureza vazia ou não cadastrada, nada é importado.
     *
     * @return array<int, ExpenseNature>
     */
    private function resolveExpenseNatures(Collection $rows): array
    {
        $natures = [];
        $invalid = [];

        foreach ($rows as $line => $row) {
            $nature = ExpenseNature::fromExpenseElement($row['expense_element']);

            if ($nature) {
                $natures[$line] = $nature;

                continue;
            }

            $invalid[$row['expense_element'] ?? ''][] = $line;
        }

        if ($invalid === []) {
            return $natures;
        }

        $details = collect($invalid)
            ->map(fn (array $lines, int|string $value) => sprintf(
                '%s (linha%s %s)',
                (string) $value === '' ? 'Elemento de Despesa vazio' : '"'.$value.'"',
                count($lines) > 1 ? 's' : '',
                implode(', ', $lines),
            ))
            ->join('; ');

        $accepted = collect(ExpenseNature::cases())
            ->map(fn (ExpenseNature $nature) => $nature->code())
            ->join(', ');

        throw new BusinessRuleException(
            'A planilha possui naturezas de despesa não cadastradas no sistema: '
            .$details
            .'. Naturezas aceitas: '.$accepted
            .'. Corrija a planilha e envie novamente; nenhuma dotação foi importada.'
        );
    }

    private function readRows(UploadedFile $file): Collection
    {
        $data = SpreadsheetImporter::import(
            file: $file,
            mapping: BudgetAllocationSpreadsheetMap::definition(),
            required: BudgetAllocationSpreadsheetMap::required(),
            headerRow: 0,
            dataStartsAt: 1,
            keyByLine: true,
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
