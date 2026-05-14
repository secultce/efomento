<?php

namespace App\Jobs;

use App\Services\RegistrationFileExtractor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class SyncProjectFilesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public int $projectId,
        public int $registrationId,
        public array $files,
        public array $fileConfigurations
    ) {
        $this->onQueue('files');
    }

    public function handle(RegistrationFileExtractor $extractor): void
    {
        $normalizedFiles = $extractor->extract(
            $this->files,
            $this->fileConfigurations
        );

        if ($normalizedFiles->isEmpty()) {
            Log::info('sync.project.files.empty', [
                'project_id' => $this->projectId,
                'registration_id' => $this->registrationId,
            ]);

            return;
        }

        $jobs = $normalizedFiles
            ->map(fn (array $file) => new DownloadMapasProjectFileJob(
                projectId: $this->projectId,
                registrationId: $this->registrationId,
                file: $file
            ))
            ->all();

        Bus::batch($jobs)
            ->name("sync-project-files:{$this->projectId}")
            ->onQueue('files')
            ->allowFailures()
            ->dispatch();

        Log::info('sync.project.files.queued', [
            'project_id' => $this->projectId,
            'registration_id' => $this->registrationId,
            'total_files' => count($jobs),
        ]);
    }

    public function backoff(): array
    {
        return [1, 5, 10];
    }
}
