<?php

namespace App\Jobs;

use App\Services\MapasClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncMonitoringJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct()
    {
        $this->onQueue('high');
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('sync-monitoring'))
                ->releaseAfter(300)
                ->expireAfter(3600),
        ];
    }

    public function handle(MapasClient $mapasClient): void
    {
        Log::info('sync.monitoring.start');

        $opportunities = $mapasClient->monitoringOpportunities();

        $valid = $opportunities->filter(fn (array $o) => filled(data_get($o, 'id')));

        if ($valid->isEmpty()) {
            Log::info('sync.monitoring.empty');

            return;
        }

        $valid->each(function (array $opportunity) {
            SyncMonitoringRegistrationsJob::dispatch((int) data_get($opportunity, 'id'))
                ->onQueue('medium');
        });

        Log::info('sync.monitoring.dispatched', [
            'total' => $opportunities->count(),
            'valid' => $valid->count(),
        ]);
    }

    public function backoff(): array
    {
        return [1, 5, 10];
    }

    public function failed(Throwable $exception): void
    {
        Log::error('sync.monitoring.failed', [
            'message' => $exception->getMessage(),
        ]);
    }
}
