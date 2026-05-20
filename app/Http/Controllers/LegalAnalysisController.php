<?php

namespace App\Http\Controllers;

use App\Enums\FileStatus;
use App\Models\File;
use App\Models\Project;
use App\Models\ProjectStage;
use App\Services\LegalAnalysisService;
use App\Services\ProjectStageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LegalAnalysisController extends Controller
{
    public function __construct(private readonly LegalAnalysisService $legalAnalysisService) {}

    public function index(Project $project): JsonResponse
    {
        $analysis = $project->legalAnalysis;
        $files = $project->files()->get();

        $grouped = $files->groupBy('grp')->map(function ($groupFiles, $grp) use ($analysis, $project) {
            return [
                'group' => $grp ?? 'Sem categoria',
                'files' => $groupFiles->map(function ($file) use ($analysis, $project) {
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
            ];
        })->values();

        return response()->json([
            'groups' => $grouped,
            'statusOptions' => FileStatus::options(),
        ]);
    }

    public function serveFile(Project $project, File $file, Request $request): StreamedResponse
    {
        $disk = Storage::disk(config('efomento.file_disk', 'public'));

        abort_unless($disk->exists($file->path), 404);

        if ($request->boolean('download')) {
            return $disk->download($file->path, $file->name);
        }

        return $disk->response($file->path, $file->name);
    }

    public function tramit(Request $request, Project $project, ProjectStageService $stageService): mixed
    {
        $data = $request->validate([
            'stage_id' => ['required', 'exists:project_stages,id'],
        ]);

        $stage = ProjectStage::with('project')->findOrFail($data['stage_id']);

        try {
            $stageService->advance($stage, $request->user());

            return back()->with('success', 'Análise jurídica tramitada com sucesso!');
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Erro ao tramitar: '.$e->getMessage());
        }
    }

    public function updateFileStatus(Request $request, Project $project, File $file): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(FileStatus::class)],
        ]);

        $this->legalAnalysisService->updateFileStatus($project, $file, FileStatus::from($data['status']));

        return response()->json(['message' => 'Status atualizado com sucesso']);
    }
}
