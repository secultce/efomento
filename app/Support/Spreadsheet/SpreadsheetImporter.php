<?php

namespace App\Support\Spreadsheet;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
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

        $aliases = self::buildAliases($mapping);

        $headers = collect($rows[$headerRow])
            ->map(function ($header) use ($aliases) {
                $normalized = Str::snake(trim((string) $header));

                return $aliases[$normalized] ?? $normalized;
            })
            ->toArray();

        self::validateRequiredColumns($headers, $required);

        return collect(array_slice($rows, $dataStartsAt))
            ->filter(function ($row) {
                return collect($row)
                    ->filter(fn ($value) => $value !== null && $value !== '')
                    ->isNotEmpty();
            })
            ->map(function ($row) use ($headers) {
                return array_combine(
                    $headers,
                    array_pad($row, count($headers), null)
                );
            })
            ->values();
    }

    protected static function buildAliases(array $mapping): array
    {
        return collect($mapping)
            ->flatMap(function (array $aliases, string $canonical) {
                return collect($aliases)
                    ->mapWithKeys(fn ($alias) => [
                        Str::snake(trim($alias)) => $canonical,
                    ]);
            })
            ->toArray();
    }

    protected static function validateRequiredColumns(
        array $headers,
        array $required
    ): void {
        $missing = collect($required)
            ->reject(fn ($column) => in_array($column, $headers, true));

        if ($missing->isNotEmpty()) {
            throw new InvalidArgumentException(
                'Spreadsheet is missing required columns: '.
                    $missing->implode(', ')
            );
        }
    }
}
