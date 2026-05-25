<?php

namespace App\Services;

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
     * Retorna o numero de linhas.
     */
    public function importSheet(string $spreadsheetId, string $sheetName, ?int $fallbackNoticeId = null): int
    {
        ['rows' => $rows] = $this->fetchSheet($spreadsheetId, $sheetName);

        $count = 0;
        foreach ($rows as $row) {
            if ($this->importService->processRow(array_values($row), $fallbackNoticeId) !== null) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Busca a aba e cria/retorna Openings para cada linha com projeto já existente.
     * Retorna o número de Openings processadas com sucesso.
     */
    public function importOpenings(string $spreadsheetId, string $sheetName): int
    {
        ['rows' => $rows] = $this->fetchSheet($spreadsheetId, $sheetName);

        $count = 0;
        foreach ($rows as $row) {
            if ($this->importService->resolveOpeningByLabels($row) !== null) {
                $count++;
            }
        }

        return $count;
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
            static fn (array $col): string => (string) ($col['label'] !== '' ? $col['label'] : $col['id']),
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
                $mapped[$column] = isset($cell['v']) ? $cell['v'] : null;
            }

            $rows[] = $mapped;
        }

        return $rows;
    }
}
