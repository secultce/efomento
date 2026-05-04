<?php

namespace App\Imports;

use App\Services\SpreadsheetImportService;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithStartRow;

class ProjectImport implements SkipsEmptyRows, ToModel, WithChunkReading, WithStartRow
{
    public function __construct(
        private readonly SpreadsheetImportService $service
    ) {}

    public function startRow(): int
    {
        return 2;
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function model(array $row): ?Model
    {
        return $this->service->processRow($row);
    }
}
