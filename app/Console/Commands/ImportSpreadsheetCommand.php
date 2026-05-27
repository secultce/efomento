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
                            {--user= : ID do usuário responsável pela importação}
                            {--with-files : Busca e baixa os arquivos vinculados às inscrições no Mapas}';

    protected $description = 'Importa projetos de uma planilha para o banco de dados';

    public function handle(SpreadsheetImportService $service): int
    {
        $path = $this->argument('path');

        if (! file_exists($path)) {
            $this->error("Arquivo não encontrado: {$path}");

            return self::FAILURE;
        }

        $userId = (int) $this->option('user');

        if (! $userId || ! User::whereKey($userId)->exists()) {
            $this->error('Informe um usuário válido com --user=ID');

            return self::FAILURE;
        }

        $withFiles = (bool) $this->option('with-files');

        $this->info("Importando planilha: {$path}");

        if ($withFiles) {
            $this->info('Os arquivos serão enfileirados para download após a importação.');
        }

        $this->newLine();

        Excel::import(
            new ProjectImport($service, $userId, $withFiles),
            $path
        );

        $this->info('Importação concluída.');

        return self::SUCCESS;
    }
}
