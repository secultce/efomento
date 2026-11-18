<?php

namespace App\Jobs;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncProjectPhasesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct()
    {
        $this->onQueue('details');
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('sync-project-phases'))
                ->releaseAfter(300)
                ->expireAfter(3600),
        ];
    }

    public function handle(): void
    {
        // O agendamento é por dia: next_check_at guarda o horário em que o projeto foi processado (que varia
        // com a fila e o rate limit), então "vencido" é qualquer data até o fim do dia de hoje no fuso do
        // agendamento. Comparar com now() puro pularia a execução de amanhã (04:00:10 > 04:00:01).
        $dueUntil = now(config('efomento.phase_sync_timezone'))
            ->endOfDay()
            ->setTimezone(config('app.timezone'));

        $ids = Project::query()
            ->whereNull('sync_closed_at')
            ->where(fn ($query) => $query
                ->whereNull('next_check_at')
                ->orWhere('next_check_at', '<=', $dueUntil))
            ->pluck('id');

        if ($ids->isEmpty()) {
            Log::info('sync.project_phases.empty');

            return;
        }

        $chunkSize = max(1, (int) config('efomento.registration_detail_chunk', 100));

        $ids->chunk($chunkSize)->each(function ($chunk, int $index) {
            Bus::batch($chunk->map(fn (int $id) => new SyncProjectPhasesProjectJob($id))->values()->all())
                ->name('sync-project-phases:chunk:'.($index + 1))
                ->onQueue('details')
                ->allowFailures()
                ->dispatch();
        });

        Log::info('sync.project_phases.dispatched', ['total' => $ids->count()]);
    }

    public function backoff(): array
    {
        return [1, 5, 10];
    }

    public function failed(Throwable $exception): void
    {
        Log::error('sync.project_phases.failed', ['message' => $exception->getMessage()]);
    }
}
