<?php

namespace App\Imports;

use App\Services\SpreadsheetImportService;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Row;

class ProjectImport implements OnEachRow, SkipsEmptyRows, WithChunkReading, WithStartRow
{
    public function __construct(
        private readonly SpreadsheetImportService $service,
        private readonly int $userId,
        private readonly bool $withFiles = false,
    ) {}

    public function startRow(): int
    {
        return 2;
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function onRow(Row $row): void
    {
        $this->service->processRow(
            row: $row->toArray(),
            withFiles: $this->withFiles,
            userId: $this->userId,
        );
    }
}
