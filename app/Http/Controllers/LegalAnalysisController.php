<?php

namespace App\Http\Controllers;

use App\Enums\FileStatus;
use App\Models\File;
use App\Models\Project;
use App\Repositories\LegalAnalysisRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LegalAnalysisController extends Controller
{
    public function __construct(private readonly LegalAnalysisRepository $legalAnalysisRepository) {}

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

        return response()->json($grouped);
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

    public function updateFileStatus(Request $request, Project $project, File $file): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(FileStatus::class)],
        ]);

        $this->legalAnalysisRepository->updateFileStatus($project, $file, FileStatus::from($data['status']));

        return response()->json(['message' => 'Status atualizado com sucesso']);
    }
}
