<?php

namespace App\Services;

use App\Jobs\SyncPhaseFilesJob;
use App\Models\File;
use App\Models\Project;
use App\Models\ProjectRegistrationPhase;

/**
 * Monta, para cada fase da inscrição, a lista dos arquivos já baixados para o e-Fomento. O `external_id` é o id
 * do arquivo no Mapas (o mesmo do campo `file` no JSON da fase), então o modal troca o link do Mapas pelo local
 * sem alterar o `data` salvo.
 */
class RegistrationPhaseFilesService
{
    private const SOURCE_MAPAS = 'mapas';

    private const OBJECT_TYPE_PROJECT = 'project';

    public function __construct(private readonly PhaseFileExtractor $extractor) {}

    /**
     * Grava em cada fase carregada o atributo `local_files`: lista de { external_id, id, name, mime_type, view_url, download_url }.
     * Lista e não mapa por id: o ProjectResource reindexa arrays com chaves numéricas (removeMissingValues).
     * Uma única consulta em `files` para o projeto inteiro.
     */
    public function attachLocalFiles(Project $project): void
    {
        $phases = $project->registrationPhases;

        $phaseExternalIds = $phases->mapWithKeys(fn (ProjectRegistrationPhase $phase) => [
            $phase->id => $this->extractor->extract($phase->data ?? [])->pluck('external_id')->map(fn ($id) => (string) $id)->all(),
        ]);

        $allIds = $phaseExternalIds->flatten()->unique()->values()->all();

        $files = $allIds === []
            ? collect()
            : File::query()
                ->where('source', self::SOURCE_MAPAS)
                ->whereIn('external_id', $allIds)
                ->where(fn ($query) => $query
                    ->where(fn ($q) => $q->where('object_type', self::OBJECT_TYPE_PROJECT)->where('object_id', $project->id))
                    ->orWhere(fn ($q) => $q->where('object_type', SyncPhaseFilesJob::OBJECT_TYPE)
                        ->whereIn('object_id', $phases->pluck('id'))))
                ->get();

        foreach ($phases as $phase) {
            $ownIds = $phaseExternalIds[$phase->id] ?? [];

            $localFiles = $files
                // A consulta já restringe os donos ao projeto; aqui só fica o que esta fase referencia.
                ->filter(fn (File $file) => in_array((string) $file->external_id, $ownIds, true))
                ->sortBy(fn (File $file) => $this->ownerRank($file, $phase))
                ->unique('external_id')
                ->map(fn (File $file) => [
                    'external_id' => (string) $file->external_id,
                    'id' => $file->id,
                    'name' => $file->name,
                    'mime_type' => $file->mime_type,
                    'view_url' => route('projects.registration-phases.files.serve', [$project, $phase, $file]),
                    'download_url' => route('projects.registration-phases.files.serve', [$project, $phase, $file, 'download' => 1]),
                ])
                ->values()
                ->all();

            $phase->setAttribute('local_files', $localFiles);
        }
    }

    /**
     * Pode servir o arquivo para esta fase: é do Mapas, pertence ao projeto (fase do projeto ou fase 1 legada
     * com dono `project`) e aparece no JSON da própria fase. Um arquivo compartilhado entre fases do mesmo
     * projeto (baixado só para uma delas) fica acessível nas outras que o referenciam.
     */
    public function canServe(File $file, ProjectRegistrationPhase $phase, Project $project): bool
    {
        if ($file->source !== self::SOURCE_MAPAS) {
            return false;
        }

        // Caso comum (arquivo da própria fase) resolve em memória; a consulta só roda para fase irmã do projeto.
        $ownedByPhase = $file->object_type === SyncPhaseFilesJob::OBJECT_TYPE
            && ((int) $file->object_id === $phase->id
                || ProjectRegistrationPhase::query()->where('project_id', $project->id)->whereKey($file->object_id)->exists());

        $ownedByProject = ($file->object_type === self::OBJECT_TYPE_PROJECT && (int) $file->object_id === $project->id)
            || $ownedByPhase;

        if (! $ownedByProject) {
            return false;
        }

        return $this->extractor->extract($phase->data ?? [])
            ->contains(fn (array $entry) => (string) $entry['external_id'] === (string) $file->external_id);
    }

    // Cópia da própria fase primeiro; depois a de outra fase do projeto (arquivo compartilhado); por fim a do projeto (fase 1 legada).
    private function ownerRank(File $file, ProjectRegistrationPhase $phase): int
    {
        if ($file->object_type === SyncPhaseFilesJob::OBJECT_TYPE) {
            return (int) $file->object_id === $phase->id ? 0 : 1;
        }

        return 2;
    }
}
