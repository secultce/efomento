<?php

namespace App\Jobs;

use App\Services\NoticeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncNoticesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('high');
    }

    /**
     * Execute the job.
     */
    public function handle(NoticeService $noticeService): void
    {
        Log::info('sync.notices.start');

        $seal = config('efomento.mapas_seal');
        $endpoint = config('efomento.mapas_domain')."/api/opportunity/find?@select=id,name,singleUrl&@seals={$seal}&publish_site=EQ(Sim)";

        $response = Http::timeout(10)
            ->retry(3, fn ($attempt) => $attempt * 1000)
            ->get($endpoint);

        if (! $response->successful()) {
            throw new \Exception('Erro ao buscar API');
        }

        $notices = collect($response->json());

        $jobs = [];

        foreach ($notices as $notice) {
            $noticeService->updateOrCreateFromMapas($notice);

            $jobs[] = new SyncNoticeRegistrationsJob($notice['id']);
        }

        collect($jobs)
            ->chunk(50)
            ->each(function ($chunk) {
                Bus::batch($chunk->all())
                    ->name('sync-notices-registrations')
                    ->onQueue('medium')
                    ->allowFailures()
                    ->then(fn () => Log::info('sync.batch.success'))
                    ->catch(fn ($e) => Log::error('sync.batch.error', ['error' => $e->getMessage()]))
                    ->dispatch();
            });

        Log::info('sync.notices.done', ['total' => $notices->count()]);
    }

    public function backoff(): array
    {
        return [1, 5, 10];
    }

    public function failed(\Throwable $exception)
    {
        Log::error('sync.notices.failed', [
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
