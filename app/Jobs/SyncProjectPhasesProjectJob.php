<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\ProjectPhaseSyncService;
use DateTimeInterface;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncProjectPhasesProjectJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 10;

    public int $timeout = 300;

    public int $maxExceptions = 3;

    public function __construct(public int $projectId)
    {
        $this->onQueue('details');
    }

    public function middleware(): array
    {
        return [
            (new RateLimited('mapas-api'))->releaseAfter(30),

            (new WithoutOverlapping("sync-project-phases:{$this->projectId}"))
                ->releaseAfter(60)
                ->expireAfter(600),
        ];
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addHours(2);
    }

    public function handle(ProjectPhaseSyncService $service): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $project = Project::find($this->projectId);

        if (! $project || $project->sync_closed_at) {
            return;
        }

        try {
            $service->sync($project);
        } catch (Throwable $exception) {
            // Estado preservado: só adia a próxima verificação. attempts() também conta as liberações
            // da fila (rate limiter e WithoutOverlapping), então o adiamento fica limitado a 24h.
            $service->postponeAfterFailure($project, min($this->attempts(), 24));

            throw $exception;
        }
    }

    public function backoff(): array
    {
        return [1, 5, 10, 30, 60];
    }

    public function failed(Throwable $exception): void
    {
        Log::error('sync.project_phases.project.failed', [
            'project_id' => $this->projectId,
            'message' => $exception->getMessage(),
        ]);
    }
}
