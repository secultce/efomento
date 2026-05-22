<?php

namespace App\Console\Commands;

use App\Imports\ProjectImport;
use App\Models\User;
use App\Services\SpreadsheetImportService;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ImportSpreadsheetCommand extends Command
{
    protected $signature = 'app:import-spreadsheet
                            {path : Caminho para o arquivo .csv ou .xlsx}
                            {--user-id= : ID do usuário responsável pela importação}';

    protected $description = 'Importa projetos de uma planilha para o banco de dados';

    public function handle(SpreadsheetImportService $service): int
    {
        $path = $this->argument('path');

        if (! file_exists($path)) {
            $this->error("Arquivo não encontrado: {$path}");

            return self::FAILURE;
        }

        $userId = $this->option('user-id');

        if (! $userId || ! User::find($userId)) {
            $this->error('Informe um --user-id válido de um usuário existente.');

            return self::FAILURE;
        }

        $service->setUserId((int) $userId);

        $this->info("Importando planilha: {$path}");
        $this->newLine();

        Excel::import(new ProjectImport($service), $path);

        $this->info('Importação concluída.');

        return self::SUCCESS;
    }
}
