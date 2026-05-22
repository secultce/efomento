<?php

namespace App\Console\Commands;

use App\Services\GoogleSheetsService;
use Illuminate\Console\Command;
use RuntimeException;

class ImportGoogleSheetsCommand extends Command
{
    protected $signature = 'app:import-google-sheets
                            {spreadsheet-id : ID da planilha pública do Google Sheets}
                            {--sheet=Sheet1 : Nome da aba a importar}
                            {--notice-id= : ID do edital (Notice) a associar quando não encontrado pela planilha}
                            {--openings : Importa Openings pelo label das colunas em vez de projetos}';

    protected $description = 'Importa projetos ou openings de uma aba do Google Sheets para o banco de dados';

    public function handle(GoogleSheetsService $service): int
    {
        $spreadsheetId = $this->argument('spreadsheet-id');
        $sheetName = $this->option('sheet');

        $this->info("Buscando aba \"{$sheetName}\" da planilha {$spreadsheetId}...");

        try {
            if ($this->option('openings')) {
                $count = $service->importOpenings($spreadsheetId, $sheetName);
                $this->info("Importação concluída: {$count} opening(s) processada(s).");
            } else {
                $noticeId = $this->option('notice-id') ? (int) $this->option('notice-id') : null;
                $count = $service->importSheet($spreadsheetId, $sheetName, $noticeId);
                $this->info("Importação concluída: {$count} projeto(s) processado(s).");
            }
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
