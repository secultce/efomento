<?php

namespace App\Jobs;

use App\Models\Notice;
use App\Models\Opening;
use App\Services\AgentService;
use App\Services\CategoryService;
use App\Services\MapasClient;
use App\Services\ProfileSnapshotService;
use App\Services\ProjectService;
use App\Support\DocumentNumber;
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
        public array $registration
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
        ProjectService $projectService,
        CategoryService $categoryService,
        AgentService $agentService
    ): void {
        if ($this->batch()?->cancelled()) {
            return;
        }

        Log::info('sync.registration.details.start', [
            'registration_id' => $this->registrationId,
        ]);

        $details = $mapasClient->registrationDetails($this->registrationId);

        $ownerId = data_get($details, 'registration.owner.id');

        if (! $ownerId) {
            Log::warning('sync.registration.owner_missing', [
                'registration_id' => $this->registrationId,
            ]);

            return;
        }

        $agentData = $mapasClient->agentById((int) $ownerId);

        $agentCpf = DocumentNumber::normalize(
            data_get($agentData, 'cpf')
        );

        if (! $agentCpf) {
            Log::warning('sync.registration.agent_cpf_missing', [
                'registration_id' => $this->registrationId,
                'owner_id' => $ownerId,
            ]);

            return;
        }

        $noticeExternalId = data_get($details, 'registration.opportunity.id');

        if (! $noticeExternalId) {
            Log::warning('sync.registration.notice_external_id_missing', [
                'registration_id' => $this->registrationId,
            ]);

            return;
        }

        DB::transaction(function () use (
            $details,
            $agentCpf,
            $agentData,
            $noticeExternalId,
            $snapshotService,
            $projectService,
            $categoryService,
            $agentService
        ) {
            $agent = $agentService->updateOrCreatedByDocument(
                cpf: $agentCpf,
                name: data_get($agentData, 'name'),
            );

            $notice = Notice::firstWhere('external_id', $noticeExternalId);

            if (! $notice) {
                throw new RuntimeException("Edital local não encontrado para external_id {$noticeExternalId} na inscrição {$this->registrationId}.");
            }

            $category = $categoryService->findOrCreateProject(
                data_get($details, 'registration.category')
            );

            $categoryId = $category?->id;

            $project = $projectService->createFromRegistrationIfMissing(
                registrationId: $this->registrationId,
                registration: $this->registration,
                details: $details,
                agentId: $agent->id,
                noticeId: $notice->id,
                categoryId: $categoryId
            );

            Opening::firstOrCreate(
                ['project_id' => $project->id],
                ['is_draft' => true]
            );

            DB::afterCommit(function () use (
                $agent,
                $agentData,
                $snapshotService,
                $project,
                $details
            ) {
                $snapshotService->recordMapasAgentIfChanged(
                    $agent,
                    $agentData
                );

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
