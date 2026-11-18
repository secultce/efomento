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
use Illuminate\Support\Facades\Log;
use Throwable;

class LoadSpreadsheetProjectFilesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 10;

    public int $timeout = 120;

    public int $maxExceptions = 3;

    public function __construct(
        public int $projectId,
        public int $registrationId,
    ) {
        $this->onQueue('details');
    }

    public function middleware(): array
    {
        return [
            (new RateLimited('mapas-api'))->releaseAfter(30),

            (new WithoutOverlapping("load-spreadsheet-project-files:{$this->registrationId}"))
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
        Log::info('spreadsheet.project.files.load.start', [
            'project_id' => $this->projectId,
            'registration_id' => $this->registrationId,
        ]);

        $details = $mapasClient->registrationDetails($this->registrationId);

        SyncProjectFilesJob::dispatch(
            projectId: $this->projectId,
            registrationId: $this->registrationId,
            files: data_get($details, 'registration.files', []),
            fileConfigurations: data_get($details, 'fileConfigurations', []),
        )->onQueue('files');

        Log::info('spreadsheet.project.files.load.dispatched', [
            'project_id' => $this->projectId,
            'registration_id' => $this->registrationId,
        ]);
    }

    public function backoff(): array
    {
        return [1, 5, 10, 30, 60];
    }

    public function failed(Throwable $exception): void
    {
        Log::error('spreadsheet.project.files.load.failed', [
            'project_id' => $this->projectId,
            'registration_id' => $this->registrationId,
            'message' => $exception->getMessage(),
        ]);
    }
}
