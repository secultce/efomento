<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncNoticeRegistrationsJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $noticeId) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Obtém as inscrições com status 10 (selecionados) para o edital específico
        $endpoint = config('efomento.mapas_domain') . "/api/opportunity/findRegistrations?@select=createTimestamp,sentTimestamp&@opportunity={$this->noticeId}&status=EQ(10)";

        $response = Http::withHeaders([
            'authorization' => config('efomento.mapas_token'),
        ])
            ->timeout(10)
            ->retry(3, fn($attempt) => $attempt * 1000)
            ->get($endpoint);

        if (!$response->successful()) {
            throw new \Exception("Erro ao buscar inscrições");
        }

        $registrations = collect($response->json());

        $registrations
            ->chunk(50) // Controle de volume
            ->each(function ($chunk) {
                ProcessRegistrationBatchJob::dispatch($chunk->values()->all())
                    ->onQueue('low');
            });

        Log::info('sync.registrations.batched', [
            'notice_external_id' => $this->noticeId,
            'total' => $registrations->count()
        ]);
    }

    public function backoff(): array
    {
        return [1, 5, 10];
    }
}
