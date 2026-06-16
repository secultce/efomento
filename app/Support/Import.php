<?php

namespace App\Support;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Str;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class Import
{
    public static function normalizeHeader(mixed $value): string
    {
        return Str::of((string) $value)
            ->trim()
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();
    }

    public static function buildAliases(array $mapping): array
    {
        return collect($mapping)
            ->flatMap(function (array $aliases, string $canonical) {
                return collect([$canonical, ...$aliases])
                    ->mapWithKeys(fn ($alias) => [
                        self::normalizeHeader($alias) => $canonical,
                    ]);
            })
            ->toArray();
    }

    public static function validateRequiredColumns(
        array $headers,
        array $required
    ): void {
        $normalizedHeaders = collect($headers)
            ->map(fn ($header) => self::normalizeHeader($header))
            ->toArray();

        $missing = collect($required)
            ->map(fn ($column) => self::normalizeHeader($column))
            ->reject(fn ($column) => in_array($column, $normalizedHeaders, true));

        if ($missing->isNotEmpty()) {
            throw new InvalidArgumentException(
                'Spreadsheet is missing required columns: '.
                    $missing->implode(', ')
            );
        }
    }

    public static function normalizeNup(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = preg_replace('/\D+/', '', (string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    public static function date(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if ($value instanceof DateTimeInterface) {
                return $value->format('Y-m-d');
            }

            if (is_numeric($value)) {
                return ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
            }

            $value = trim((string) $value);

            foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'd/m/y', 'd-m-y'] as $format) {
                $date = Carbon::createFromFormat($format, $value);

                if ($date !== false) {
                    return $date->format('Y-m-d');
                }
            }

            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    public static function money(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $value = trim((string) $value);

        /**
         * Handles:
         * 1.234,56
         * R$ 1.234,56
         */
        $value = preg_replace('/[^\d,.-]/', '', $value);

        if (str_contains($value, ',')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }

        return is_numeric($value) ? (float) $value : null;
    }

    public static function string(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
