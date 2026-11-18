<?php

namespace App\Jobs;

use App\Services\MapasClient;
use App\Services\NoticeService;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncNoticesJob implements ShouldQueue
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
            (new WithoutOverlapping('sync-notices'))
                ->releaseAfter(300)
                ->expireAfter(3600),
        ];
    }

    public function handle(
        MapasClient $mapasClient,
        NoticeService $noticeService
    ): void {
        Log::info('sync.notices.start');

        $notices = $mapasClient->publishedNotices();

        $jobs = $notices
            ->filter(fn (array $notice) => filled(data_get($notice, 'id')))
            ->map(function (array $notice) use ($noticeService) {
                $noticeService->createFromMapasIfMissing($notice);

                return (new SyncNoticeRegistrationsJob((int) data_get($notice, 'id')))
                    ->onQueue('medium');
            })
            ->values();

        if ($jobs->isEmpty()) {
            Log::info('sync.notices.empty');

            return;
        }

        Bus::batch($jobs->all())
            ->name('sync-notices-registration-loaders')
            ->onQueue('medium')
            ->allowFailures()
            ->then(function (Batch $batch) {
                Log::info('sync.batch.success', [
                    'batch_id' => $batch->id,
                    'total_jobs' => $batch->totalJobs,
                    'processed_jobs' => $batch->processedJobs(),
                    'failed_jobs' => $batch->failedJobs,
                ]);
            })
            ->catch(function (Batch $batch, Throwable $e) {
                Log::error('sync.batch.error', [
                    'batch_id' => $batch->id,
                    'error' => $e->getMessage(),
                ]);
            })
            ->finally(function (Batch $batch) {
                Log::info('sync.notice_registration_loaders.finished', [
                    'batch_id' => $batch->id,
                    'total_jobs' => $batch->totalJobs,
                    'processed_jobs' => $batch->processedJobs(),
                    'pending_jobs' => $batch->pendingJobs,
                    'failed_jobs' => $batch->failedJobs,
                    'progress' => $batch->progress(),
                ]);
            })
            ->dispatch();

        Log::info('sync.notices.registration_jobs_dispatched', [
            'total' => $notices->count(),
            'valid' => $jobs->count(),
        ]);
    }

    public function backoff(): array
    {
        return [1, 5, 10];
    }

    public function failed(Throwable $exception): void
    {
        Log::error('sync.notices.failed', [
            'message' => $exception->getMessage(),
        ]);
    }
}
