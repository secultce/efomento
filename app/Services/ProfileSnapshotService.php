<?php

namespace App\Services;

use App\Enums\ProfileSnapshotSource;
use App\Models\ProfileSnapshot;
use App\Support\DocumentNumber;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;

class ProfileSnapshotService
{
    private const FIELDS = [
        'cpf_cnpj',
        'gender',
        'sexual_orientation',
        'race',
        'education',
        'has_disability',
        'phone',
        'secondary_phone',
        'email',
        'secondary_email',
        'birth_date',
        'street',
        'number',
        'complement',
        'postal_code',
        'neighborhood',
        'city',
        'state',
    ];

    private const MAPAS_AGENT_FIELD_MAP = [
        'cpf' => 'cpf_cnpj',
        'genero' => 'gender',
        'orientacaoSexual' => 'sexual_orientation',
        'raca' => 'race',
        'escolaridade' => 'education',
        'pessoaDeficiente' => 'has_disability',

        'telefone1' => 'phone',
        'telefone2' => 'secondary_phone',
        'emailPrivado' => 'email',
        'emailPublico' => 'secondary_email',
        'dataDeNascimento' => 'birth_date',

        'En_Nome_Logradouro' => 'street',
        'En_Num' => 'number',
        'En_Complemento' => 'complement',
        'En_CEP' => 'postal_code',
        'En_Bairro' => 'neighborhood',
        'En_Municipio' => 'city',
        'En_Estado' => 'state',
    ];

    public function record(
        Model $model,
        array $data,
        ProfileSnapshotSource $source
    ): ProfileSnapshot {
        $payload = $this->onlySnapshotFields($data);

        return $model->profileSnapshots()->create([
            ...$payload,
            'source' => $source,
            'recorded_at' => now(),
        ]);
    }

    public function recordIfChanged(
        Model $model,
        array $data,
        ProfileSnapshotSource $source
    ): ?ProfileSnapshot {
        $payload = $this->onlySnapshotFields($data);

        if ($source === ProfileSnapshotSource::PROJECT_REGISTRATION) {
            return $model->profileSnapshots()->create([
                ...$payload,
                'source' => $source,
                'recorded_at' => now(),
            ]);
        }

        $existing = $model->profileSnapshots()
            ->where('source', $source->value)
            ->latest('recorded_at')
            ->first();

        if ($existing === null) {
            return $model->profileSnapshots()->create([
                ...$payload,
                'source' => $source,
                'recorded_at' => now(),
            ]);
        }

        if ($this->isSameData($existing, $payload)) {
            return null;
        }

        return $model->profileSnapshots()->create([
            ...$payload,
            'source' => $source,
            'recorded_at' => now(),
        ]);
    }

    public function recordMapasAgentIfChanged(
        Model $model,
        array $mapasAgentData,
        ProfileSnapshotSource $source = ProfileSnapshotSource::AGENT_UPDATE
    ): ?ProfileSnapshot {
        $normalized = $this->normalizeMapasAgentData($mapasAgentData);

        return $this->recordIfChanged($model, $normalized, $source);
    }

    private function onlySnapshotFields(array $data): array
    {
        return array_intersect_key($data, array_flip(self::FIELDS));
    }

    private function normalizeMapasAgentData(array $data): array
    {
        $normalized = [];

        foreach (self::MAPAS_AGENT_FIELD_MAP as $mapasField => $snapshotField) {
            if (! array_key_exists($mapasField, $data)) {
                continue;
            }

            $value = $data[$mapasField];

            $normalized[$snapshotField] = match ($snapshotField) {
                'birth_date' => $this->normalizeDate($value),
                'cpf_cnpj' => DocumentNumber::normalize($value) ?: null,
                default => $this->normalizeString($value),
            };
        }

        return $normalized;
    }

    private function normalizeString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeDate(mixed $value): ?string
    {
        return $this->normalizeString($value);
    }

    private function isSameData(ProfileSnapshot $snapshot, array $incoming): bool
    {
        foreach ($incoming as $field => $value) {
            $stored = $snapshot->getRawOriginal($field);
            $normalized = $value instanceof BackedEnum ? $value->value : $value;

            if ($stored !== $normalized) {
                return false;
            }
        }

        return true;
    }
}
