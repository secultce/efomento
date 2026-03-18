<?php

namespace App\Console\Commands;

use App\Imports\ProjectImport;
use App\Services\SpreadsheetImportService;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ImportSpreadsheetCommand extends Command
{
    protected $signature = 'app:import-spreadsheet
                            {path : Caminho para o arquivo .csv ou .xlsx}';

    protected $description = 'Importa projetos de uma planilha para o banco de dados';

    public function handle(SpreadsheetImportService $service): int
    {
        $path = $this->argument('path');
//        $noticeId = (int) $this->option('notice-id');

        if (! file_exists($path)) {
            $this->error("Arquivo não encontrado: {$path}");
            return self::FAILURE;
        }

        $this->info("Importando planilha: {$path}");
//        $this->info("Notice ID: {$noticeId}");
        $this->newLine();

        Excel::import(new ProjectImport($service), $path);

        $this->info('Importação concluída.');

        return self::SUCCESS;
    }
}
