<?php

namespace Tests\Unit;

use App\Services\GoogleSheetsService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GoogleSheetsIdTest extends TestCase
{
    #[DataProvider('spreadsheetInputs')]
    public function test_extracts_only_a_sheet_id_or_a_google_sheets_url(string $input, ?string $expected): void
    {
        $this->assertSame($expected, GoogleSheetsService::extractSpreadsheetId($input));
    }

    public static function spreadsheetInputs(): array
    {
        return [
            ['sheet_123-ABC', 'sheet_123-ABC'],
            ['  sheet_123-ABC  ', 'sheet_123-ABC'],
            ['https://docs.google.com/spreadsheets/d/sheet_123-ABC/edit?usp=sharing#gid=0', 'sheet_123-ABC'],
            ['https://docs.google.com/spreadsheets/d/sheet_123-ABC', 'sheet_123-ABC'],
            ['', null],
            ['not a sheet', null],
            ['https://example.com/spreadsheets/d/sheet-id/edit', null],
            ['https://docs.google.com.evil.test/spreadsheets/d/sheet-id/edit', null],
            ['https://docs.google.com@evil.test/spreadsheets/d/sheet-id/edit', null],
            ['https://user@docs.google.com/spreadsheets/d/sheet-id/edit', null],
            ['https://docs.google.com/spreadsheets/d/', null],
            ['https://docs.google.com/spreadsheets/d/e/published-id/pubhtml', null],
            ['https://docs.google.com/document/d/document-id/edit', null],
            ['https://docs.google.com/spreadsheets/d/sheet%20id/edit', null],
        ];
    }
}
