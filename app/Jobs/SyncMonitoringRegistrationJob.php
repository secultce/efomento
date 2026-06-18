<?php

namespace App\Jobs;

use App\Enums\ProfileSnapshotSource;
use App\Models\Monitoring;
use App\Models\ProfileSnapshot;
use App\Models\Project;
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
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncMonitoringRegistrationJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 10;

    public int $timeout = 120;

    public int $maxExceptions = 3;

    public function __construct(public int $registrationId)
    {
        $this->onQueue('details');
    }

    public function middleware(): array
    {
        return [
            (new RateLimited('mapas-api'))->releaseAfter(30),

            (new WithoutOverlapping("sync-monitoring-registration:{$this->registrationId}"))
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

        $details = $mapasClient->registrationDetails($this->registrationId);

        $number = data_get($details, 'registration.number');

        if (! $number) {
            Log::warning('sync.monitoring.registration.number_missing', [
                'registration_id' => $this->registrationId,
            ]);

            return;
        }

        $project = Project::where('number', $number)->first();

        if (! $project) {
            Log::warning('sync.monitoring.registration.project_not_found', [
                'registration_id' => $this->registrationId,
                'number' => $number,
            ]);

            return;
        }

        Monitoring::updateOrCreate(
            ['project_id' => $project->id],
            ['data_registration' => $details]
        );

        ProfileSnapshot::updateOrCreate(
            [
                'object_id' => $project->id,
                'object_type' => 'project',
                'source' => ProfileSnapshotSource::MONITORING,
            ],
            ['recorded_at' => now()]
        );

        Log::info('sync.monitoring.registration.done', [
            'registration_id' => $this->registrationId,
            'project_id' => $project->id,
        ]);
    }

    public function backoff(): array
    {
        return [1, 5, 10, 30, 60];
    }

    public function failed(Throwable $exception): void
    {
        Log::error('sync.monitoring.registration.failed', [
            'registration_id' => $this->registrationId,
            'message' => $exception->getMessage(),
        ]);
    }
}
