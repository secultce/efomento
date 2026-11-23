<?php

namespace App\Services;

use App\Enums\FileStatus;
use App\Enums\ProjectStageSlug;
use App\Enums\ProjectStageStatus;
use App\Enums\Role;
use App\Exceptions\Domain\BusinessRuleException;
use App\Exceptions\Domain\DomainAuthorizationException;
use App\Models\File;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LegalAnalysisService
{
    public function updateFileStatus(Project $project, File $file, FileStatus $status, User $user): void
    {
        if (! $user->hasAnyRole(Role::legalAnalysisRoles())) {
            throw new DomainAuthorizationException('Usuário não tem permissão para avaliar documentos.');
        }

        // A leitura da etapa e a gravação ficam na mesma transação, com a etapa bloqueada: se a tramitação
        // confirmar antes, a leitura já vê APROVADO e nada é gravado.
        DB::transaction(function () use ($project, $file, $status) {
            $stage = $project->stages()
                ->where('slug', ProjectStageSlug::ANALISE_JURIDICA)
                ->lockForUpdate()
                ->first();

            if ($stage?->status === ProjectStageStatus::APROVADO) {
                throw new BusinessRuleException('Análise jurídica já foi tramitada.', httpStatus: 422);
            }

            $analysis = $project->legalAnalysis()->firstOrCreate([]);

            $analysis->files()->updateOrCreate(
                ['file_id' => $file->id],
                ['status' => $status],
            );
        });
    }

    public function isAnalysisFile(Project $project, File $file): bool
    {
        return $project->analysisFilesQuery()->whereKey($file->id)->exists();
    }

    /**
     * Arquivos sob avaliação, cada um com o status já avaliado (uma única consulta). Os que vieram do Mapas
     * (inscrição) são agrupados por fase; o do projeto, do fluxo antigo, conta como fase 1. Arquivos
     * anexados de outra origem (upload, planilha) mantêm o agrupamento por `grp`, no fim da lista.
     *
     * O mesmo arquivo do Mapas pode existir com dono `project` e com dono fase (corrida da fase 1). Fica a
     * linha que já tem avaliação, para não perder o que a ASJUR avaliou; sem avaliação, a do projeto.
     *
     * @return Collection<int, array{key: string, group: string, phase_order: ?int, files: Collection<int, array{file: File, status: ?FileStatus}>}>
     */
    public function groupedAnalysisFiles(Project $project): Collection
    {
        // Arquivos antes dos status: uma cópia gravada entre as duas leituras não entra na lista, em vez de
        // entrar sem o status da outra cópia.
        $files = $project->analysisFilesQuery()->get();
        $statuses = $project->legalAnalysis?->files()->pluck('status', 'file_id') ?? collect();
        $phaseOrders = $project->registrationPhases()->pluck('phase_order', 'id');

        $phaseOf = function (File $file) use ($phaseOrders): ?int {
            if ($file->source !== 'mapas') {
                return null;
            }

            return $file->object_type === 'project' ? 1 : (int) ($phaseOrders[$file->object_id] ?? 1);
        };

        return $files
            ->sortBy([
                fn (File $a, File $b) => $statuses->has($b->id) <=> $statuses->has($a->id),
                fn (File $a, File $b) => ($b->object_type === 'project') <=> ($a->object_type === 'project'),
                fn (File $a, File $b) => $a->id <=> $b->id,
            ])
            ->unique(fn (File $file) => $file->external_id ? "mapas:{$file->external_id}" : "id:{$file->id}")
            ->groupBy(fn (File $file) => ($order = $phaseOf($file)) !== null
                ? sprintf('0-phase-%05d', $order)
                : '1-grp-'.($file->grp ?? 'Sem categoria'))
            ->sortKeys()
            ->map(fn (Collection $files, string $key) => [
                'key' => $key,
                'group' => ($order = $phaseOf($files->first())) !== null ? "Fase {$order}" : ($files->first()->grp ?? 'Sem categoria'),
                'phase_order' => $phaseOf($files->first()),
                'files' => $files->sortBy('id')->map(fn (File $file) => [
                    'file' => $file,
                    'status' => $statuses->get($file->id),
                ])->values(),
            ])
            ->values();
    }
}
