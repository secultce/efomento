<?php

namespace App\Support\Spreadsheet;

use App\Support\Import;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class SpreadsheetImporter
{
    public static function import(
        $file,
        array $mapping = [],
        array $required = [],
        int $headerRow = 1,
        int $dataStartsAt = 2
    ): Collection {
        $sheets = Excel::toArray([], $file);

        $rows = $sheets[0] ?? [];

        if (count($rows) <= $headerRow) {
            return collect();
        }

        $aliases = Import::buildAliases($mapping);

        $headers = collect($rows[$headerRow])
            ->map(function ($header) use ($aliases) {
                $normalized = Import::normalizeHeader($header);

                return $aliases[$normalized] ?? $normalized;
            })
            ->toArray();

        Import::validateRequiredColumns($headers, $required);

        return collect(array_slice($rows, $dataStartsAt))
            ->filter(function ($row) {
                return collect($row)
                    ->filter(fn ($value) => $value !== null && $value !== '')
                    ->isNotEmpty();
            })
            ->map(function ($row) use ($headers) {
                $values = array_slice(
                    array_pad($row, count($headers), null),
                    0,
                    count($headers)
                );

                return array_combine($headers, $values);
            })
            ->values();
    }
}
