<?php

namespace App\Http\Controllers;

use App\Enums\FileStatus;
use App\Models\File;
use App\Models\Project;
use App\Repositories\LegalAnalysisRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LegalAnalysisController extends Controller
{
    public function __construct(private readonly LegalAnalysisRepository $legalAnalysisRepository) {}

    public function index(Project $project): JsonResponse
    {
        $analysis = $project->legalAnalysis;
        $files = $project->files()->get();

        $grouped = $files->groupBy('grp')->map(function ($groupFiles, $grp) use ($analysis) {
            return [
                'group' => $grp ?? 'Sem categoria',
                'files' => $groupFiles->map(function ($file) use ($analysis) {
                    $status = $analysis?->getFileStatus($file->id);

                    return [
                        'id' => $file->id,
                        'name' => $file->name,
                        'url' => $file->url,
                        'status' => $status?->value,
                        'status_label' => $status?->label(),
                    ];
                })->values(),
            ];
        })->values();

        return response()->json($grouped);
    }

    public function updateFileStatus(Request $request, Project $project, File $file): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(FileStatus::class)],
        ]);

        $this->legalAnalysisRepository->updateFileStatus($project, $file, FileStatus::from($data['status']));

        return response()->json(['message' => 'Status atualizado com sucesso']);
    }
}
