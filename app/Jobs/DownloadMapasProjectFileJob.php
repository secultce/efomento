<?php

namespace App\Jobs;

use App\Models\File as StoredFile;
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
use Illuminate\Support\Facades\File as Filesystem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class DownloadMapasProjectFileJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 10;
    public int $timeout = 180;
    public int $maxExceptions = 3;

    public function __construct(
        public int $projectId,
        public int $registrationId,
        public array $file
    ) {
        $this->onQueue('files');
    }

    public function middleware(): array
    {
        return [
            (new RateLimited('mapas-api'))->releaseAfter(30),

            (new WithoutOverlapping(
                "download-mapas-project-file:{$this->projectId}:{$this->file['external_id']}"
            ))
                ->releaseAfter(60)
                ->expireAfter(900),
        ];
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addHours(4);
    }

    public function handle(MapasClient $mapasClient): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $disk = config('efomento.file_disk', 'public');

        $extension = pathinfo(
            (string) $this->file['name'],
            PATHINFO_EXTENSION
        );

        $filename = $extension
            ? "{$this->file['external_id']}.{$extension}"
            : (string) $this->file['external_id'];

        $storagePath = sprintf(
            'projects/%d/mapas/%s',
            $this->projectId,
            $filename
        );

        $existingFile = StoredFile::withTrashed()
            ->where('object_type', 'project')
            ->where('object_id', $this->projectId)
            ->where('source', 'mapas')
            ->where('external_id', (string) $this->file['external_id'])
            ->first();

        if ($existingFile) {
            Log::info('sync.project.file.skipped_already_registered', [
                'project_id' => $this->projectId,
                'registration_id' => $this->registrationId,
                'mapas_file_id' => $this->file['external_id'],
                'path' => $storagePath,
                'trashed' => $existingFile->trashed(),
            ]);

            return;
        }

        $temporaryDirectory = storage_path('app/tmp/mapas-files');
        Filesystem::ensureDirectoryExists($temporaryDirectory);

        $temporaryPath = $temporaryDirectory . '/' . uniqid('mapas_', true);

        try {
            $download = $mapasClient->downloadFileTo(
                $this->file['url'],
                $temporaryPath
            );

            $stream = fopen($temporaryPath, 'r');

            if ($stream === false) {
                throw new RuntimeException(
                    "Não foi possível abrir o arquivo temporário: {$temporaryPath}"
                );
            }

            try {
                $stored = Storage::disk($disk)->writeStream(
                    $storagePath,
                    $stream
                );
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            if (!$stored) {
                throw new RuntimeException(
                    "Falha ao gravar arquivo no storage: {$storagePath}"
                );
            }

            StoredFile::create([
                'mime_type' => $download['mime_type'],
                'name' => $this->file['name'],
                'object_type' => 'project',
                'object_id' => $this->projectId,
                'source' => 'mapas',
                'external_id' => (string) $this->file['external_id'],
                'grp' => $this->file['grp'],
                'title' => $this->file['title'],
                'description' => $this->file['description'],
                'path' => $storagePath,
                'private' => $this->file['private'],
            ]);

            Log::info('sync.project.file.created', [
                'project_id' => $this->projectId,
                'registration_id' => $this->registrationId,
                'mapas_file_id' => $this->file['external_id'],
                'path' => $storagePath,
            ]);
        } catch (Throwable $exception) {
            $fileWasRegistered = StoredFile::withTrashed()
                ->where('object_type', 'project')
                ->where('object_id', $this->projectId)
                ->where('source', 'mapas')
                ->where('external_id', (string) $this->file['external_id'])
                ->exists();

            if (!$fileWasRegistered) {
                Storage::disk($disk)->delete($storagePath);
            }

            throw $exception;
        } finally {
            if (file_exists($temporaryPath)) {
                @unlink($temporaryPath);
            }
        }
    }

    public function backoff(): array
    {
        return [1, 5, 10, 30, 60];
    }

    public function failed(Throwable $exception): void
    {
        Log::error('sync.project.file.failed', [
            'project_id' => $this->projectId,
            'registration_id' => $this->registrationId,
            'external_file_id' => data_get($this->file, 'external_id'),
            'message' => $exception->getMessage(),
        ]);
    }
}
