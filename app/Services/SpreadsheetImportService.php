<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\AgentStatus;
use App\Enums\DisabilityType;
use App\Enums\ProfileSnapshotSource;
use App\Jobs\LoadSpreadsheetProjectFilesJob;
use App\Models\Notice;
use App\Models\Opening;
use App\Models\Project;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SpreadsheetImportService
{
    public function __construct(
        private readonly ProfileSnapshotService $snapshotService,
        private readonly CategoryService $categoryService,
        private readonly AgentService $agentService
    ) {}

    /**
     * Processa uma linha da planilha e persiste Category, Agent e Project.
     * Retorna o Project criado, ou null se a linha não tiver os dados mínimos.
     */
    public function processRow(
        array $row,
        bool $withFiles,
        int $userId,
    ): ?Project {
        return DB::transaction(function () use ($row, $withFiles, $userId) {
            $registrationUrl = trim((string) ($row[44] ?? ''));
            $registrationId = $this->extractRegistrationId($registrationUrl);

            if (! $registrationId) {
                return null;
            }

            $agent = $this->agentService->updateOrCreateByCpf(
                cpf: $row[33] ?? null,
                name: $row[2] ?? null,
            );

            if (! $agent) {
                Log::warning('spreadsheet.import.agent_cpf_missing', [
                    'registration_id' => $registrationId,
                    'cpf_column' => $row[33] ?? null,
                ]);

                return null;
            }

            $noticeExternalId = trim((string) ($row[0] ?? ''));

            $notice = Notice::where('external_id', $noticeExternalId)->first();

            if (! $notice) {
                Log::warning('spreadsheet.import.notice_missing', [
                    'notice_external_id' => $noticeExternalId,
                    'registration_id' => $registrationId,
                ]);

                return null;
            }

            $category = $this->categoryService->findOrCreateProject(
                $row[4] ?? null
            );

            $snapshotData = $this->buildSnapshotData($row);

            $this->snapshotService->recordIfChanged(
                $agent,
                $snapshotData,
                ProfileSnapshotSource::AGENT_UPDATE,
            );

            $project = Project::firstOrCreate(
                ['registration_id' => $registrationId],
                [
                    'number' => 'on-'.$registrationId,
                    'category_id' => $category?->id,
                    'agent_id' => $agent->id,
                    'notice_id' => $notice->id,
                    'title_project' => trim((string) ($row[6] ?? '')) ?: null,
                ]
            );

            $this->snapshotService->recordIfChanged(
                $project,
                $snapshotData,
                ProfileSnapshotSource::PROJECT_REGISTRATION,
            );

            $this->resolveOpening($row, $project->id, $userId);

            if ($withFiles) {
                LoadSpreadsheetProjectFilesJob::dispatch(
                    projectId: $project->id,
                    registrationId: (int) $registrationId,
                )
                    ->afterCommit()
                    ->onQueue('details');
            }

            return $project;
        }, 3);
    }

    private function buildSnapshotData(array $row): array
    {
        return [
            'gender' => trim((string) ($row[40] ?? '')) ?: null,
            'has_disability' => $this->mapDisability(trim((string) ($row[42] ?? ''))),
            'email' => trim((string) ($row[36] ?? '')) ?: null,
            'phone' => trim((string) ($row[38] ?? '')) ?: null,
            'birth_date' => $this->parseDate(trim((string) ($row[41] ?? ''))),
            'street' => trim((string) ($row[34] ?? '')) ?: null,
            'city' => trim((string) ($row[35] ?? '')) ?: null,
        ];
    }

    private function resolveOpening(array $row, int $projectId, int $userId): Opening
    {
        return Opening::firstOrCreate(
            ['project_id' => $projectId],
            [
                'opening_nup' => trim((string) ($row[14] ?? '')),
                'opening_date' => $this->parseDate(trim((string) ($row[15] ?? ''))),
                'agent_status' => $this->mapAgentStatus(trim((string) ($row[1] ?? ''))),
                'opened_by' => trim((string) ($row[13] ?? '')),
                'bank' => trim((string) ($row[16] ?? '')),
                'account_type' => $this->mapAccountType(trim((string) ($row[17] ?? ''))),
                'branch' => trim((string) ($row[18] ?? '')),
                'account' => trim((string) ($row[19] ?? '')),
                'is_draft' => true,
                'certificate_date' => $this->parseDate(trim((string) ($row[12] ?? ''))),
                'started_at' => $this->parseDate(trim((string) ($row[15] ?? ''))),
                'user_id' => $userId,
            ]
        );
    }

    private function extractRegistrationId(string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path)) {
            return null;
        }

        $id = basename($path);

        return ctype_digit($id) ? $id : null;
    }

    private function mapAgentStatus(string $value): AgentStatus
    {
        return match ($value) {
            'Habilitado', 'Ativo' => AgentStatus::ATIVO,
            'Suplente' => AgentStatus::SUPLENTE,
            'Em análise' => AgentStatus::EM_ANALISE,
            'Desclassificado' => AgentStatus::DESCLASSIFICADO,
            'Desistente' => AgentStatus::DESISTENTE,
            default => AgentStatus::EM_ANALISE,
        };
    }

    private function mapAccountType(string $value): AccountType
    {
        return str_contains(strtolower($value), 'poupan')
            ? AccountType::POUPANCA
            : AccountType::CORRENTE;
    }

    private function mapDisability(string $value): DisabilityType
    {
        return match ($value) {
            'Auditiva' => DisabilityType::AUDITIVA,
            'Física' => DisabilityType::FISICA_MOTORA,
            'Intelectual' => DisabilityType::INTELECTUAL,
            'Múltipla' => DisabilityType::MULTIPLA,
            'Visual' => DisabilityType::VISUAL,
            default => DisabilityType::NAO,
        };
    }

    private function parseDate(string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Exception) {
            return null;
        }
    }
}
