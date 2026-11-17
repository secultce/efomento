<?php

namespace App\Http\Controllers;

use App\Enums\FileStatus;
use App\Models\File;
use App\Models\Project;
use App\Services\LegalAnalysisService;
use App\Support\SafeFileResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LegalAnalysisController extends Controller
{
    public function __construct(private readonly LegalAnalysisService $legalAnalysisService) {}

    public function index(Project $project): JsonResponse
    {
        $analysis = $project->legalAnalysis;
        $phaseOrders = $project->registrationPhases()->pluck('phase_order', 'id');

        // Sem fase no fluxo antigo, o arquivo do projeto é da fase 1. Se o mesmo arquivo do Mapas também foi
        // baixado como arquivo de fase, vale a linha do projeto (é a que pode já ter status).
        $files = $project->analysisFiles()->orderBy('id')->get()
            ->sortBy(fn (File $file) => $file->object_type === 'project' ? 0 : 1)
            ->unique(fn (File $file) => $file->external_id ? "mapas:{$file->external_id}" : "id:{$file->id}");

        $grouped = $files
            ->groupBy(fn (File $file) => $file->object_type === 'project' ? 1 : (int) ($phaseOrders[$file->object_id] ?? 1))
            ->sortKeys()
            ->map(fn ($groupFiles, $phaseOrder) => [
                'group' => "Fase {$phaseOrder}",
                'phase_order' => $phaseOrder,
                'files' => $groupFiles->sortBy('id')->map(function (File $file) use ($analysis, $project) {
                    $status = $analysis?->getFileStatus($file->id);

                    return [
                        'id' => $file->id,
                        'name' => $file->name,
                        'url' => route('legal-analysis.files.serve', [$project, $file]),
                        'status' => $status?->value,
                        'status_label' => $status?->label(),
                        'title' => $file->title,
                    ];
                })->values(),
            ])
            ->values();

        return response()->json([
            'groups' => $grouped,
            'statusOptions' => FileStatus::options(),
        ]);
    }

    public function serveFile(Project $project, File $file, Request $request): StreamedResponse
    {
        $this->assertFileBelongsToProject($project, $file);

        return SafeFileResponse::make($file, $request->boolean('download'));
    }

    public function updateFileStatus(Request $request, Project $project, File $file): JsonResponse
    {
        $this->assertFileBelongsToProject($project, $file);

        $data = $request->validate([
            'status' => ['required', Rule::enum(FileStatus::class)],
        ]);

        $this->legalAnalysisService->updateFileStatus($project, $file, FileStatus::from($data['status']));

        return response()->json(['message' => 'Status atualizado com sucesso']);
    }

    private function assertFileBelongsToProject(Project $project, File $file): void
    {
        abort_unless($project->analysisFiles()->whereKey($file->id)->exists(), 404);
    }
}
