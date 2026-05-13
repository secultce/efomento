<?php

namespace App\Jobs;

use App\Enums\CategoryType;
use App\Models\Agent;
use App\Models\Category;
use App\Models\Notice;
use App\Services\MapasClient;
use App\Services\ProfileSnapshotService;
use App\Services\ProjectService;
use DateTimeInterface;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SyncRegistrationDetailsJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 10;
    public int $timeout = 120;
    public int $maxExceptions = 3;

    public function __construct(
        public int $registrationId,
        public array $registration = []
    ) {
        $this->onQueue('details');
    }

    public function middleware(): array
    {
        return [
            (new RateLimited('mapas-api'))->releaseAfter(30),

            (new WithoutOverlapping("sync-registration-details:{$this->registrationId}"))
                ->releaseAfter(60)
                ->expireAfter(600),
        ];
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addHours(2);
    }

    public function handle(
        MapasClient $mapasClient,
        ProfileSnapshotService $snapshotService,
        ProjectService $projectService
    ): void {
        if ($this->batch()?->cancelled()) {
            return;
        }

        Log::info('sync.registration.details.start', [
            'registration_id' => $this->registrationId,
        ]);

        $details = $mapasClient->registrationDetails($this->registrationId);

        $ownerId = data_get($details, 'registration.owner.id');

        if (!$ownerId) {
            Log::warning('sync.registration.owner_missing', [
                'registration_id' => $this->registrationId,
            ]);

            return;
        }

        $agentData = $mapasClient->agentById((int) $ownerId);

        $cpf = data_get($agentData, 'cpf');

        if (!$cpf) {
            Log::warning('sync.registration.agent_cpf_missing', [
                'registration_id' => $this->registrationId,
                'owner_id' => $ownerId,
            ]);

            return;
        }

        $noticeExternalId = data_get($details, 'registration.opportunity.id');

        if (!$noticeExternalId) {
            Log::warning('sync.registration.notice_external_id_missing', [
                'registration_id' => $this->registrationId,
            ]);

            return;
        }

        DB::transaction(function () use (
            $details,
            $agentData,
            $cpf,
            $noticeExternalId,
            $snapshotService,
            $projectService
        ) {
            $agent = Agent::updateOrCreate(
                ['cpf' => $cpf],
                [
                    'name' => data_get($agentData, 'name') ?: 'Nome não informado',
                ]
            );

            $notice = Notice::firstWhere('external_id', $noticeExternalId);

            if (!$notice) {
                throw new RuntimeException("Edital local não encontrado para external_id {$noticeExternalId} na inscrição {$this->registrationId}.");
            }

            $categoryId = $this->resolveCategory($details);

            $project = $projectService->createFromRegistrationIfMissing(
                registrationId: $this->registrationId,
                registration: $this->registration,
                details: $details,
                agentId: $agent->id,
                noticeId: $notice->id,
                categoryId: $categoryId
            );

            DB::afterCommit(function () use (
                $agent,
                $agentData,
                $snapshotService,
                $project,
                $details
            ) {
                // É preciso parear os valores dos enuns com os valores do Mapas
                /* $snapshotService->recordMapasAgentIfChanged(
                    $agent,
                    $agentData
                ); */

                SyncProjectFilesJob::dispatch(
                    projectId: $project->id,
                    registrationId: $this->registrationId,
                    files: data_get($details, 'registration.files', []),
                    fileConfigurations: data_get($details, 'fileConfigurations', [])
                )->onQueue('files');
            });
        }, 3);

        Log::info('sync.registration.details.done', [
            'registration_id' => $this->registrationId,
        ]);
    }

    private function resolveCategory(array $details): ?int
    {
        $name = trim((string) data_get($details, 'registration.category', ''));

        if ($name === '') {
            return null;
        }

        return Category::firstOrCreate([
            'name' => $name,
            'type' => CategoryType::PROJETO->value,
        ])->id;
    }

    public function backoff(): array
    {
        return [1, 5, 10, 30, 60];
    }

    public function failed(Throwable $exception): void
    {
        Log::error('sync.registration.failed', [
            'registration_id' => $this->registrationId,
            'message' => $exception->getMessage(),
        ]);
    }
}
