<?php

namespace App\Console\Commands;

use App\Jobs\SyncPhaseFilesJob;
use App\Models\ProjectRegistrationPhase;
use Illuminate\Console\Command;

class SyncPhaseFilesCommand extends Command
{
    protected $signature = 'app:sync-phase-files {--project= : ID do projeto (todos, se omitido)}';

    protected $description = 'Enfileira o download dos arquivos das fases de inscrição já sincronizadas';

    public function handle(): int
    {
        $count = 0;

        ProjectRegistrationPhase::query()
            ->when($this->option('project'), fn ($query, $projectId) => $query->where('project_id', (int) $projectId))
            ->select('id')
            ->orderBy('id')
            ->chunkById(200, function ($phases) use (&$count) {
                foreach ($phases as $phase) {
                    SyncPhaseFilesJob::dispatch($phase->id);
                    $count++;
                }
            });

        $this->info("Enfileirados {$count} job(s) de arquivos de fase.");

        return Command::SUCCESS;
    }
}
