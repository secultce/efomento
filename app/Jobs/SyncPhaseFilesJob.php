<?php

namespace App\Jobs;

use App\Models\File;
use App\Models\ProjectRegistrationPhase;
use App\Services\PhaseFileExtractor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

/**
 * Baixa para o e-Fomento os arquivos dos campos `file` de uma fase da inscrição. Recebe só o id da fase
 * porque o `data` é grande; o download em si fica no DownloadMapasProjectFileJob.
 */
class SyncPhaseFilesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const OBJECT_TYPE = 'project_registration_phase';

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public int $phaseId)
    {
        $this->onQueue('files');
    }

    public function handle(PhaseFileExtractor $extractor): void
    {
        $phase = ProjectRegistrationPhase::find($this->phaseId);

        if (! $phase) {
            return;
        }

        $files = $extractor->extract($phase->data ?? []);

        if ($files->isEmpty()) {
            return;
        }

        // O id do arquivo no Mapas é único; quem já foi baixado (por esta fase, por outra ou pelo fluxo
        // de detalhes da inscrição) fica de fora antes de virar job, para não gastar o rate limit do Mapas.
        $registered = File::withTrashed()
            ->where('source', 'mapas')
            ->whereIn('external_id', $files->pluck('external_id')->map(fn ($id) => (string) $id)->all())
            ->pluck('external_id')
            ->all();

        $pending = $files
            ->reject(fn (array $file) => in_array((string) $file['external_id'], $registered, true))
            ->values();

        if ($pending->isEmpty()) {
            return;
        }

        $jobs = $pending
            ->map(fn (array $file) => new DownloadMapasProjectFileJob(
                projectId: $phase->project_id,
                registrationId: $phase->registration_id,
                file: $file,
                objectType: self::OBJECT_TYPE,
                objectId: $phase->id,
            ))
            ->all();

        Bus::batch($jobs)
            ->name("sync-phase-files:{$phase->project_id}:{$phase->registration_id}")
            ->onQueue('files')
            ->allowFailures()
            ->dispatch();

        Log::info('sync.phase.files.queued', [
            'project_id' => $phase->project_id,
            'registration_id' => $phase->registration_id,
            'total_files' => count($jobs),
            'already_registered' => $files->count() - count($jobs),
        ]);
    }

    public function backoff(): array
    {
        return [1, 5, 10];
    }
}
