<?php

namespace App\Services;

use App\Enums\FileStatus;
use App\Models\File;
use App\Models\Project;
use Illuminate\Support\Collection;

class LegalAnalysisService
{
    public function updateFileStatus(Project $project, File $file, FileStatus $status): void
    {
        $analysis = $project->legalAnalysis()->firstOrCreate([]);

        $analysis->files()->updateOrCreate(
            ['file_id' => $file->id],
            ['status' => $status],
        );
    }

    public function isAnalysisFile(Project $project, File $file): bool
    {
        return $project->analysisFilesQuery()->whereKey($file->id)->exists();
    }

    /**
     * Arquivos sob avaliação agrupados por fase (o do projeto, do fluxo antigo, conta como fase 1), cada um
     * com o status já avaliado. Status vêm de uma única consulta.
     *
     * O mesmo arquivo do Mapas pode existir com dono `project` e com dono fase (corrida da fase 1). Fica a
     * linha que já tem avaliação, para não perder o que a ASJUR avaliou; sem avaliação, a do projeto.
     *
     * @return Collection<int, array{group: string, phase_order: int, files: Collection<int, array{file: File, status: ?FileStatus}>}>
     */
    public function groupedAnalysisFiles(Project $project): Collection
    {
        $statuses = $project->legalAnalysis?->files()->pluck('status', 'file_id') ?? collect();
        $phaseOrders = $project->registrationPhases()->pluck('phase_order', 'id');

        return $project->analysisFilesQuery()->get()
            ->sortBy([
                fn (File $a, File $b) => $statuses->has($b->id) <=> $statuses->has($a->id),
                fn (File $a, File $b) => ($b->object_type === 'project') <=> ($a->object_type === 'project'),
                fn (File $a, File $b) => $a->id <=> $b->id,
            ])
            ->unique(fn (File $file) => $file->external_id ? "mapas:{$file->external_id}" : "id:{$file->id}")
            ->groupBy(fn (File $file) => $file->object_type === 'project' ? 1 : (int) ($phaseOrders[$file->object_id] ?? 1))
            ->sortKeys()
            ->map(fn (Collection $files, int $phaseOrder) => [
                'group' => "Fase {$phaseOrder}",
                'phase_order' => $phaseOrder,
                'files' => $files->sortBy('id')->map(fn (File $file) => [
                    'file' => $file,
                    'status' => $statuses->get($file->id),
                ])->values(),
            ])
            ->values();
    }
}
