<?php

namespace App\Jobs;

use App\Services\MapasClient;
use DateTimeInterface;
use Illuminate\Bus\Batchable;
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

class SyncNoticeRegistrationsJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 10;

    public int $timeout = 120;

    public int $maxExceptions = 3;

    public function __construct(public int $noticeId)
    {
        $this->onQueue('medium');
    }

    public function middleware(): array
    {
        return [
            (new RateLimited('mapas-api'))->releaseAfter(30),

            (new WithoutOverlapping("sync-notice-registrations:{$this->noticeId}"))
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
        if ($this->batch()?->cancelled()) {
            return;
        }

        $registrations = $mapasClient->selectedRegistrations($this->noticeId);

        $missingIdCount = $registrations
            ->filter(fn (array $registration) => blank(data_get($registration, 'id')))
            ->count();

        $jobs = $registrations
            ->filter(fn (array $registration) => filled(data_get($registration, 'id')))
            ->map(function (array $registration) {
                return new SyncRegistrationDetailsJob(
                    registrationId: (int) data_get($registration, 'id'),
                    registration: $registration
                );
            })
            ->values();

        if ($missingIdCount > 0) {
            Log::warning('sync.registrations.missing_id', [
                'notice_external_id' => $this->noticeId,
                'count' => $missingIdCount,
            ]);
        }

        if ($jobs->isEmpty()) {
            Log::info('sync.registrations.empty', [
                'notice_external_id' => $this->noticeId,
            ]);

            return;
        }

        $chunkSize = (int) config('efomento.registration_detail_chunk', 100);

        $chunks = $jobs->chunk($chunkSize);

        $chunks->each(function ($chunk, int $index) {
            Bus::batch($chunk->all())
                ->name("sync-registration-details:notice:{$this->noticeId}:chunk:".($index + 1))
                ->onQueue('details')
                ->allowFailures()
                ->dispatch();
        });

        Log::info('sync.registrations.details_batches_dispatched', [
            'notice_external_id' => $this->noticeId,
            'total_registrations' => $registrations->count(),
            'valid_registrations' => $jobs->count(),
            'detail_batches' => $chunks->count(),
            'chunk_size' => $chunkSize,
        ]);
    }

    public function backoff(): array
    {
        return [1, 5, 10, 30, 60];
    }

    public function failed(Throwable $exception): void
    {
        Log::error('sync.registrations.failed', [
            'notice_external_id' => $this->noticeId,
            'message' => $exception->getMessage(),
        ]);
    }
}
