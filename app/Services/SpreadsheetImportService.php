<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\AgentStatus;
use App\Enums\CategoryType;
use App\Enums\DisabilityType;
use App\Enums\ProfileSnapshotSource;
use App\Models\Agent;
use App\Models\Category;
use App\Models\Notice;
use App\Models\Opening;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class SpreadsheetImportService
{
    public function __construct(
        private readonly ProfileSnapshotService $snapshotService,
    ) {}

    /**
     * Processa uma linha da planilha e persiste Category, Agent e Project.
     * Retorna o Project criado, ou null se a linha não tiver os dados mínimos.
     */
    public function processRow(array $row): ?Model
    {
        $registrationUrl = trim($row[44] ?? '');
        $registrationId = $this->extractRegistrationId($registrationUrl);

        if (! $registrationId) {
            return null;
        }

        $category = $this->resolveCategory(trim($row[4] ?? ''));
        $agent = $this->resolveAgent($row);
        $notice = Notice::where('external_id', $row[0])->first();

        $this->snapshotService->recordIfChanged(
            $agent,
            $this->buildSnapshotData($row),
            ProfileSnapshotSource::AGENT_UPDATE,
        );

        $project = Project::firstOrCreate(
            ['registration_id' => $registrationId],
            [
                'number' => 'on-'.$registrationId,
                'category_id' => $category->id,
                'agent_id' => $agent->id,
                'notice_id' => $notice->id,
                'title_project' => $row[6],
            ]
        );

        $this->snapshotService->recordIfChanged(
            $project,
            $this->buildSnapshotData($row),
            ProfileSnapshotSource::PROJECT_REGISTRATION,
        );

        $this->resolveOpening($row, $project->id);

        return $project;
    }

    private function resolveAgent(array $row): Agent
    {
        $cpf = trim($row[33] ?? '');

        return Agent::updateOrCreate(
            ['cpf' => $cpf],
            ['name' => trim($row[2] ?? '')]
        );
    }

    private function buildSnapshotData(array $row): array
    {
        return [
            'gender' => trim($row[40] ?? '') ?: null,
            'has_disability' => $this->mapDisability(trim($row[42] ?? '')),
            'email' => trim($row[36] ?? '') ?: null,
            'phone' => trim($row[38] ?? '') ?: null,
            'birth_date' => $this->parseDate(trim($row[41] ?? '')),
            'street' => trim($row[34] ?? '') ?: null,
            'city' => trim($row[35] ?? '') ?: null,
        ];
    }

    private function resolveOpening(array $row, int $projectId): Opening
    {
        $user = User::find(Auth::user()->id);

        return Opening::firstOrCreate(
            ['project_id' => $projectId],
            [
                'opening_nup' => trim($row[14] ?? ''),
                'opening_date' => $this->parseDate(trim($row[15] ?? '')),
                'agent_status' => $this->mapAgentStatus(trim($row[1] ?? '')),
                'opened_by' => trim($row[13] ?? ''),
                'bank' => trim($row[16] ?? ''),
                'account_type' => $this->mapAccountType(trim($row[17] ?? '')),
                'branch' => trim($row[18] ?? ''),
                'account' => trim($row[19] ?? ''),
                'is_draft' => true,
                'certificate_date' => $this->parseDate(trim($row[12] ?? '')),
                'started_at' => $this->parseDate(trim($row[15] ?? '')),
                'user_id' => $user->id,
            ]
        );
    }

    private function resolveCategory(string $name): Category
    {
        return Category::firstOrCreate(
            ['name' => $name],
            ['type' => CategoryType::PROJETO]
        );
    }

    private function extractRegistrationId(string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $id = basename(parse_url($url, PHP_URL_PATH));

        return $id ?: null;
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
