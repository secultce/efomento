<?php

namespace App\Services;

use App\Enums\CategoryType;
use App\Enums\DisabilityType;
use App\Enums\Gender;
use App\Models\Agent;
use App\Models\Category;
use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class SpreadsheetImportService
{
    /**
     * Processa uma linha da planilha e persiste Category, Agent e Project.
     * Retorna o Project criado, ou null se a linha não tiver os dados mínimos.
     */
    public function processRow(array $row, int $noticeId): ?Model
    {
        $registrationUrl = trim($row[44] ?? '');
        $registrationId = $this->extractRegistrationId($registrationUrl);

        if (! $registrationId) {
            return null;
        }

        $category = $this->resolveCategory(trim($row[4] ?? ''));
        $agent = $this->resolveAgent($row);

        return Project::firstOrCreate(
            ['registration_id' => $registrationId],
            [
                'number' => 'on-' . $registrationId,
                'category_id' => $category->id,
                'agent_id' => $agent->id,
                'notice_id' => $noticeId,
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

    private function resolveAgent(array $row): Agent
    {
        $cpf = trim($row[33] ?? '');

        return Agent::updateOrCreate(
            ['cpf' => $cpf],
            [
                'name' => trim($row[2] ?? ''),
                'street' => trim($row[34] ?? ''),
                'city' => trim($row[35] ?? ''),
                'email' => trim($row[36] ?? ''),
                'phone' => trim($row[38] ?? ''),
                'gender' => $this->mapGender(trim($row[40] ?? '')),
                'birth_date' => $this->parseDate(trim($row[41] ?? '')),
                'has_disability' => $this->mapDisability(trim($row[42] ?? '')),
            ]
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

    private function mapGender(string $value): Gender
    {
        return match ($value) {
            'Homem Cis' => Gender::MASCULINO,
            'Mulher Cis' => Gender::FEMININO,
            'Mulher Trans/travesti', 'Não Binárie/outra variabilidade' => Gender::OUTRO,
            default => Gender::PREFERE_NAO_RESPONDER,
        };
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
