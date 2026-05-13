<?php

namespace App\Jobs;

use App\Enums\CategoryType;
use App\Enums\ProfileSnapshotSource;
use App\Models\Agent;
use App\Models\Category;
use App\Models\Notice;
use App\Services\ProfileSnapshotService;
use App\Services\ProjectService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncRegistrationDetailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public array $registration) {}

    public int $tries = 3;

    public int $timeout = 120;

    /**
     * Execute the job.
     */
    public function handle(
        ProfileSnapshotService $snapshotService,
        ProjectService $projectService
    ): void {
        $id = $this->registration['id'];

        Log::info('sync.registration.details', ['id' => $id]);

        $details = $this->fetchDetails($id);

        if (! $details) {
            return;
        }

        $agentData = $this->getAgent($details);
        $agent = null;

        DB::transaction(function () use ($id, $details, $agentData, &$agent, $projectService) {
            $agent = Agent::firstOrCreate(
                ['cpf' => $agentData['cpf']],
                ['name' => $agentData['name']]
            );

            $categoryId = $this->resolveCategory($details);

            $notice = Notice::firstWhere(
                'external_id',
                data_get($details, 'registration.opportunity.id')
            );

            if (! $notice) {
                Log::warning('sync.registration.notice_not_found', ['registration' => $id]);

                return;
            }

            try {
                $projectService->updateOrCreateFromRegistration(
                    $id,
                    $this->registration,
                    $details,
                    $agent->id,
                    $notice->id,
                    $categoryId
                );
            } catch (QueryException $e) {
                if ($e->getCode() === '23505') { // postgres unique violation
                    Log::warning('sync.registration.duplicate', ['id' => $id]);
                } else {
                    throw $e;
                }
            }
        });

        if ($agent) {
            DB::afterCommit(function () use ($agent, $agentData, $snapshotService) {
                $snapshotService->recordIfChanged(
                    $agent,
                    $agentData,
                    ProfileSnapshotSource::AGENT_UPDATE,
                );
            });
        }
    }

    private function fetchDetails(int $id): ?array
    {
        $endpoint = config('efomento.mapas_domain')."/registration/detalhes/{$id}";

        $response = Http::withHeaders([
            'authorization' => config('efomento.mapas_token'),
        ])
            ->timeout(10)
            ->retry(3, fn ($attempt) => $attempt * 1000)
            ->get($endpoint);

        if (! $response->successful()) {
            throw new \Exception("Erro ao buscar detalhes {$id}");
        }

        return $response->json();
    }

    private function getAgent(array $details): array
    {
        $id = data_get($details, 'registration.owner.id');

        if (! $id) {
            throw new \Exception('Owner não encontrado');
        }

        return Cache::remember("agent_{$id}", 3600, function () use ($id) {
            return Cache::lock("agent_lock_{$id}", 10)->block(5, function () use ($id) {
                $response = Http::withHeaders([
                    'authorization' => config('efomento.mapas_token'),
                ])
                    ->timeout(10)
                    ->retry(3, fn ($attempt) => $attempt * 1000)
                    ->get(config('efomento.mapas_domain')."/api/agent/findOne?@select=id,name,cpf,genero,orientacaoSexual,raca&id=EQ({$id})");

                if (! $response->successful()) {
                    throw new \Exception('Erro ao buscar agente');
                }

                return $response->json();
            });
        });
    }

    private function resolveCategory(array $details): ?int
    {
        $name = $details['registration']['category'];

        if (! $name) {
            return null;
        }

        return Category::firstOrCreate([
            'name' => $name,
            'type' => CategoryType::PROJETO->value,
        ])->id;
    }

    public function backoff(): array
    {
        return [1, 5, 10];
    }

    public function failed(\Throwable $exception)
    {
        Log::error('sync.registration.failed', [
            'registration_id' => $this->registration['id'],
            'message' => $exception->getMessage(),
        ]);
    }
}
