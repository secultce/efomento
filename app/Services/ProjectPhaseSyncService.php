<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectRegistrationPhase;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use UnexpectedValueException;

class ProjectPhaseSyncService
{
    private const STATUS_DRAFT = 0;

    private const STATUS_SENT = 1;

    private const STATUS_INVALID = 2;

    private const STATUS_NOT_SELECTED = 3;

    private const STATUS_WAITLIST = 8;

    private const STATUS_SELECTED = 10;

    private const RECHECK_DAYS = 7;

    public function __construct(private readonly MapasClient $mapasClient) {}

    public function sync(Project $project): void
    {
        $summary = $this->mapasClient->registrationAllPhases(
            (int) $project->registration_id,
            untilLatest: true,
            summary: true,
        );

        $hash = $this->summaryHash($summary);
        $changed = $hash !== $project->summary_hash;

        if (! $changed) {
            [$nextCheckAt, $close] = $this->schedule($summary, false);

            $this->updateControl($project, [
                'last_checked_at' => now(),
                'next_check_at' => $nextCheckAt,
                ...($close ? ['sync_closed_at' => now()] : []),
            ]);

            return;
        }

        // Requisições ao Mapas ficam fora da transação: ela só faz leituras e gravações no banco.
        $resolved = collect(data_get($summary, 'phases', []))
            ->map(fn (array $phaseSummary) => $this->resolvePhase($project, $phaseSummary))
            ->filter()
            ->all();

        DB::transaction(function () use ($project, $summary, $hash, $resolved) {
            foreach ($resolved as $phase) {
                $this->storePhase($project, $phase);
            }

            [$nextCheckAt, $close] = $this->schedule($summary, true);

            $this->updateControl($project, [
                'latest_registration_id' => data_get($summary, 'latestRegistrationId'),
                'total_phases' => data_get($summary, 'totalPhases'),
                'summary_hash' => $hash,
                'last_checked_at' => now(),
                'last_changed_at' => now(),
                'next_check_at' => $nextCheckAt,
                'sync_closed_at' => $close ? now() : null,
            ]);
        });

        Log::info('sync.project_phases.changed', [
            'project_id' => $project->id,
            'total_phases' => data_get($summary, 'totalPhases'),
        ]);
    }

    public function postponeAfterFailure(Project $project, int $attempt): void
    {
        $this->updateControl($project, [
            'next_check_at' => now()->addHours(max(1, $attempt)),
        ]);
    }

    public function summaryHash(array $summary): string
    {
        $normalized = [
            'latestRegistrationId' => data_get($summary, 'latestRegistrationId'),
            'totalPhases' => data_get($summary, 'totalPhases'),
            'phases' => collect(data_get($summary, 'phases', []))
                ->map(fn (array $phase) => [
                    'registrationId' => data_get($phase, 'registrationId'),
                    'status' => data_get($phase, 'status'),
                    'sentDate' => data_get($phase, 'sentDate'),
                    'registrationFrom' => data_get($phase, 'opportunity.registrationFrom'),
                    'registrationTo' => data_get($phase, 'opportunity.registrationTo'),
                    'hasNextPhase' => (bool) data_get($phase, 'hasNextPhase'),
                ])
                ->all(),
        ];

        return $this->hash($normalized);
    }

    /**
     * @return array{0: ?Carbon, 1: bool} [próxima verificação, encerrar sincronização]
     */
    public function schedule(array $summary, bool $changed): array
    {
        $latest = collect(data_get($summary, 'phases', []))
            ->firstWhere('registrationId', data_get($summary, 'latestRegistrationId'));

        $tomorrow = now()->addDay();

        if (! $latest) {
            return [$tomorrow, false];
        }

        $status = (int) data_get($latest, 'status');

        if (in_array($status, [self::STATUS_DRAFT, self::STATUS_SENT], true)) {
            return [$tomorrow, false];
        }

        $hasNext = (bool) data_get($latest, 'hasNextPhase');

        $final = in_array($status, [self::STATUS_SELECTED, self::STATUS_INVALID, self::STATUS_NOT_SELECTED], true);

        if ($final && ! $hasNext) {
            return [null, true];
        }

        if ($changed) {
            return [$tomorrow, false];
        }

        $next = now()->addDays(self::RECHECK_DAYS);

        $nextFrom = data_get($latest, 'nextPhase.registrationFrom');

        if ($hasNext && $nextFrom) {
            $beforeOpening = Carbon::parse($nextFrom)->subDay();
            $next = $beforeOpening->lt($next) ? $beforeOpening : $next;
        }

        return [$next->lt($tomorrow) ? $tomorrow : $next, false];
    }

    /**
     * Busca no Mapas o conteúdo da fase quando ela é nova ou mudou; null quando não há o que gravar.
     *
     * @return array{registration_id: int, data: array, content_hash: string, status: mixed}|null
     */
    private function resolvePhase(Project $project, array $phaseSummary): ?array
    {
        $registrationId = (int) data_get($phaseSummary, 'registrationId');

        $existing = $project->registrationPhases()
            ->where('registration_id', $registrationId)
            ->first();

        if ($existing && ! $this->summaryDiffers($existing, $phaseSummary)) {
            return null;
        }

        $full = $this->mapasClient->registrationAllPhases($registrationId);

        $data = collect(data_get($full, 'phases', []))
            ->firstWhere('registrationId', $registrationId);

        // Sem o detalhe não se grava o resumo: o summary_hash faria as próximas rodadas pularem a fase.
        if (! is_array($data)) {
            throw new UnexpectedValueException("Mapas returned no detail for registration {$registrationId}");
        }

        $contentHash = $this->hash($data);
        $status = data_get($data, 'status');

        if ($existing && $existing->content_hash === $contentHash && $existing->status === $status) {
            return null;
        }

        return [
            'registration_id' => $registrationId,
            'data' => $data,
            'content_hash' => $contentHash,
            'status' => $status,
        ];
    }

    private function storePhase(Project $project, array $phase): void
    {
        $data = $phase['data'];

        ProjectRegistrationPhase::updateOrCreate(
            ['project_id' => $project->id, 'registration_id' => $phase['registration_id']],
            [
                'opportunity_id' => data_get($data, 'opportunity.id'),
                'phase_order' => (int) data_get($data, 'order'),
                'is_first' => (bool) data_get($data, 'isFirstPhase'),
                'status' => $phase['status'],
                'sent_at' => $this->parseSentDate(data_get($data, 'sentDate')),
                'registration_from' => data_get($data, 'opportunity.registrationFrom'),
                'registration_to' => data_get($data, 'opportunity.registrationTo'),
                'data' => $data,
                'content_hash' => $phase['content_hash'],
                'fetched_at' => now(),
            ]
        );
    }

    private function summaryDiffers(ProjectRegistrationPhase $phase, array $summary): bool
    {
        return $phase->status !== data_get($summary, 'status')
            || $phase->sent_at?->format('d/m/Y') !== data_get($summary, 'sentDate')
            || $phase->registration_from?->toIso8601String() !== $this->iso(data_get($summary, 'opportunity.registrationFrom'))
            || $phase->registration_to?->toIso8601String() !== $this->iso(data_get($summary, 'opportunity.registrationTo'));
    }

    private function iso(?string $value): ?string
    {
        return $value ? Carbon::parse($value)->setTimezone(config('app.timezone'))->toIso8601String() : null;
    }

    private function parseSentDate(?string $value): ?Carbon
    {
        return $value ? Carbon::createFromFormat('d/m/Y', $value)->startOfDay() : null;
    }

    private function updateControl(Project $project, array $attributes): void
    {
        // Query base: não dispara auditoria nem observers nem altera updated_at a cada consulta diária.
        Project::query()->whereKey($project->id)->toBase()->update($attributes);

        $project->forceFill($attributes)->syncOriginal();
    }

    private function hash(array $content): string
    {
        return hash('sha256', json_encode(
            $this->sortKeys($content),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));
    }

    private function sortKeys(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->sortKeys($item);
            }
        }

        if (! array_is_list($value)) {
            ksort($value);
        }

        return $value;
    }
}
