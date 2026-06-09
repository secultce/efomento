<?php

namespace App\Console\Commands;

use App\Services\DiligenceMessageService;
use Illuminate\Console\Command;

class SyncDiligenceEmailsCommand extends Command
{
    protected $signature = 'app:sync-diligence-emails';

    protected $description = 'Sincroniza respostas de diligências recebidas via IMAP';

    public function handle(DiligenceMessageService $service): int
    {
        try {
            $count = $service->syncIncoming();
            $this->info("Sincronizadas {$count} mensagem(ns) de diligência.");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            report($e);
            $this->error("Falha ao sincronizar diligências via IMAP: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
