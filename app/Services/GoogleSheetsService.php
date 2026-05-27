<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Formalization;
use App\Models\Project;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleSheetsService
{
    private const GVIZ_ENDPOINT = 'https://docs.google.com/spreadsheets/d/%s/gviz/tq';

    public function __construct(
        private readonly SpreadsheetImportService $importService,
    ) {}

    /**
     * Fetches all rows from a public Google Sheet tab and returns a structured array.
     *
     * @return array{columns: string[], rows: array<int, array<string, mixed>>}
     *
     * @throws RuntimeException
     */
    public function fetchSheet(string $spreadsheetId, string $sheetName): array
    {
        $url = sprintf(self::GVIZ_ENDPOINT, $spreadsheetId);

        try {
            $raw = Http::timeout(30)
                ->get($url, ['tqx' => 'out:json', 'sheet' => $sheetName])
                ->throw()
                ->body();
        } catch (RequestException $e) {
            throw new RuntimeException("Failed to fetch Google Sheet: {$e->getMessage()}", previous: $e);
        }
        $json = $this->stripSecurityPrefix($raw);

        $table = $this->decodeTable($json);

        $columns = $this->extractColumns($table);

        return [
            'columns' => $columns,
            'rows' => $this->extractRows($table, $columns),
        ];
    }

    /**
     * Usando o SpreadsheetImportService::processRow().
     * Retorna o número de linhas gravadas.
     */
    public function importSheet(string $spreadsheetId, string $sheetName, bool $withFiles, int $userId, ?int $fallbackNoticeId = null): int
    {
        ['rows' => $rows] = $this->fetchSheet($spreadsheetId, $sheetName);

        $count = 0;
        foreach ($rows as $row) {
            if ($this->importService->processRow($row, $withFiles, $userId, $fallbackNoticeId) !== null) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Sincroniza a aba de Formalização com o model Formalization.
     * Retorna o número de registros gravados.
     */
    public function syncFormalization(string $spreadsheetId, string $sheetName, int $userId): int
    {
        $config = config('spreadsheet_mappings.formalizacao');
        $columnMap = $config['column_map'];
        $projectLookupColumn = $config['column_for_project_lookup'];

        ['rows' => $rows] = $this->fetchSheet($spreadsheetId, $sheetName);

        $count = 0;
        foreach ($rows as $row) {
            if ($row['STATUS'] === 'Desclassificado' || $row['STATUS'] === 'Desistente') {
                continue;
            }

            $project = $this->resolveProjectByNumber($row[$projectLookupColumn] ?? null);

            if (! $project) {
                continue;
            }

            $record = ['process_supervisor_id' => $userId, 'created_by' => $userId];
            foreach ($columnMap as $sheetColumn => $modelField) {
                $record[$modelField] = $row[$sheetColumn] ?? null;
            }

            Formalization::updateOrCreate(['project_id' => $project->id], $record);
            $count++;
        }

        return $count;
    }

    /**
     * Sincroniza a aba de Orçamento com o model Budget.
     * Retorna o número de registros gravados.
     */
    public function syncBudget(string $spreadsheetId, string $sheetName, int $userId): int
    {
        $config = config('spreadsheet_mappings.orcamento');
        $columnMap = $config['column_map'];
        $projectLookupColumn = $config['column_for_project_lookup'];

        ['rows' => $rows] = $this->fetchSheet($spreadsheetId, $sheetName);

        $count = 0;
        foreach ($rows as $row) {
            $project = $this->resolveProjectByNumber($row[$projectLookupColumn] ?? null);

            if (! $project) {
                continue;
            }

            $record = ['created_by' => $userId];
            foreach ($columnMap as $sheetColumn => $modelField) {
                $record[$modelField] = $row[$sheetColumn] ?? null;
            }

            Budget::updateOrCreate(['project_id' => $project->id], $record);
            $count++;
        }

        return $count;
    }

    private function resolveProjectByNumber(mixed $number): ?Project
    {
        if (! $number) {
            return null;
        }

        return Project::where('number', $number)->first();
    }

    /**
     * Importa linhas de uma aba para qualquer model Eloquent usando um mapa de colunas declarativo.
     *
     * @param  class-string  $modelClass
     * @param  array<string, string>  $columnMap  chave = label ou id da coluna na planilha; valor = campo fillable do model
     * @param  string|string[]  $uniqueBy  campo(s) usados como chave no upsert; vazio = insert simples
     * @param  array<string, mixed>  $defaults  campos com valor fixo mesclados em cada registro (ex: created_by, process_supervisor_id)
     * @return int número de linhas gravadas
     */
    public function importToModel(
        string $spreadsheetId,
        string $sheetName,
        string $modelClass,
        array $columnMap,
        string|array $uniqueBy = [],
        array $defaults = [],
    ): int {
        ['rows' => $rows, 'lookup' => $lookup] = $this->fetchSheetWithLookup($spreadsheetId, $sheetName);

        $records = [];
        foreach ($rows as $row) {
            $record = $defaults;
            foreach ($columnMap as $sheetColumn => $modelField) {
                $rowKey = $lookup[$sheetColumn] ?? null;
                $record[$modelField] = $rowKey !== null ? ($row[$rowKey] ?? null) : null;
            }
            $records[] = $record;
        }

        if (empty($records)) {
            return 0;
        }

        $updateColumns = array_unique(array_merge(array_values($columnMap), array_keys($defaults)));

        if (empty($uniqueBy)) {
            $modelClass::insert($records);
        } else {
            $modelClass::upsert($records, (array) $uniqueBy, $updateColumns);
        }

        return count($records);
    }

    /**
     * Igual a fetchSheet() mas também devolve um lookup label/id → rowKey,
     * permitindo que o columnMap aceite tanto o label quanto o id da coluna.
     *
     * @return array{columns: string[], rows: array<int, array<string, mixed>>, lookup: array<string, string>}
     */
    private function fetchSheetWithLookup(string $spreadsheetId, string $sheetName): array
    {
        $url = sprintf(self::GVIZ_ENDPOINT, $spreadsheetId);

        try {
            $raw = Http::timeout(30)
                ->get($url, ['tqx' => 'out:json', 'sheet' => $sheetName])
                ->throw()
                ->body();
        } catch (RequestException $e) {
            throw new RuntimeException("Failed to fetch Google Sheet: {$e->getMessage()}", previous: $e);
        }

        $table = $this->decodeTable($this->stripSecurityPrefix($raw));
        $columns = $this->extractColumns($table);
        $rows = $this->extractRows($table, $columns);

        // Monta lookup: label → rowKey e id → rowKey (rowKey = label ?: id)
        $lookup = [];
        foreach ($table['cols'] ?? [] as $index => $col) {
            $rowKey = $columns[$index];
            $label = (string) ($col['label'] ?? '');
            $id = (string) ($col['id'] ?? '');

            if ($label !== '') {
                $lookup[$label] = $rowKey;
            }
            if ($id !== '') {
                $lookup[$id] = $rowKey;
            }
        }

        return ['columns' => $columns, 'rows' => $rows, 'lookup' => $lookup];
    }

    /**
     * Strips the /*O_o*\/\ngoogle.visualization.Query.setResponse(...); wrapper.
     */
    private function stripSecurityPrefix(string $raw): string
    {
        // Extract JSON from inside the outer setResponse( ... ) call.
        if (preg_match('/google\.visualization\.Query\.setResponse\((.*)\);?\s*$/s', $raw, $matches)) {
            return $matches[1];
        }

        // Fallback: try to find the first '{' and last '}' in the string.
        $start = strpos($raw, '{');
        $end = strrpos($raw, '}');

        if ($start === false || $end === false) {
            throw new RuntimeException('Could not locate JSON payload in Google Sheets response.');
        }

        return substr($raw, $start, $end - $start + 1);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeTable(string $json): array
    {
        $decoded = json_decode($json, associative: true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Invalid JSON from Google Sheets: '.json_last_error_msg());
        }

        $table = $decoded['table'] ?? null;

        if (! is_array($table)) {
            throw new RuntimeException('Unexpected Google Sheets response structure: missing "table" key.');
        }

        return $table;
    }

    /** @return string[] */
    private function extractColumns(array $table): array
    {
        return array_map(
            static fn (array $col): string => (string) (($col['label'] ?? '') !== '' ? $col['label'] : ($col['id'] ?? '')),
            $table['cols'] ?? [],
        );
    }

    /**
     * @param  string[]  $columns
     * @return array<int, array<string, mixed>>
     */
    private function extractRows(array $table, array $columns): array
    {
        $rows = [];

        foreach ($table['rows'] ?? [] as $rawRow) {
            $cells = $rawRow['c'] ?? [];
            $mapped = [];

            foreach ($columns as $index => $column) {
                $cell = $cells[$index] ?? null;
                $mapped[$column] = isset($cell['v']) ? $this->parseGvizValue($cell['v']) : null;
            }

            $rows[] = $mapped;
        }

        return $rows;
    }

    /**
     * Converte o formato gviz de data "Date(Y,M,D)" (mês 0-indexado) para "Y-m-d".
     * Outros valores são devolvidos sem alteração.
     */
    private function parseGvizValue(mixed $value): mixed
    {
        if (is_string($value) && preg_match('/^Date\((\d+),(\d+),(\d+)\)$/', $value, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[1], (int) $m[2] + 1, (int) $m[3]);
        }

        return $value;
    }
}
