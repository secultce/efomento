<?php

namespace App\Jobs;

use App\Services\MapasClient;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncMonitoringRegistrationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 10;

    public int $timeout = 120;

    public int $maxExceptions = 3;

    public function __construct(public int $opportunityId)
    {
        $this->onQueue('medium');
    }

    public function middleware(): array
    {
        return [
            (new RateLimited('mapas-api'))->releaseAfter(30),

            (new WithoutOverlapping("sync-monitoring-registrations:{$this->opportunityId}"))
                ->releaseAfter(60)
                ->expireAfter(600),
        ];
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addHours(2);
    }

    public function handle(MapasClient $mapasClient): void
    {
        $registrations = $mapasClient->monitoringRegistrations($this->opportunityId);

        $jobs = $registrations
            ->filter(fn (array $r) => filled(data_get($r, 'id')))
            ->map(fn (array $r) => new SyncMonitoringRegistrationJob((int) data_get($r, 'id')))
            ->values();

        if ($jobs->isEmpty()) {
            Log::info('sync.monitoring.registrations.empty', [
                'opportunity_id' => $this->opportunityId,
            ]);

            return;
        }

        $chunkSize = (int) config('efomento.registration_detail_chunk', 100);

        $jobs->chunk($chunkSize)->each(function ($chunk, int $index) {
            Bus::batch($chunk->all())
                ->name("sync-monitoring-details:opportunity:{$this->opportunityId}:chunk:".($index + 1))
                ->onQueue('details')
                ->allowFailures()
                ->dispatch();
        });

        Log::info('sync.monitoring.registrations.dispatched', [
            'opportunity_id' => $this->opportunityId,
            'total' => $registrations->count(),
            'valid' => $jobs->count(),
        ]);
    }

    public function backoff(): array
    {
        return [1, 5, 10, 30, 60];
    }

    public function failed(Throwable $exception): void
    {
        Log::error('sync.monitoring.registrations.failed', [
            'opportunity_id' => $this->opportunityId,
            'message' => $exception->getMessage(),
        ]);
    }
}
