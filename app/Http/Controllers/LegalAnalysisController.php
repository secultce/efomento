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
        $grouped = $this->legalAnalysisService->groupedAnalysisFiles($project)
            ->map(fn (array $group) => [
                'group' => $group['group'],
                'phase_order' => $group['phase_order'],
                'files' => $group['files']->map(fn (array $item) => [
                    'id' => $item['file']->id,
                    'name' => $item['file']->name,
                    'url' => route('legal-analysis.files.serve', [$project, $item['file']]),
                    'status' => $item['status']?->value,
                    'status_label' => $item['status']?->label(),
                    'title' => $item['file']->title,
                ])->values(),
            ]);

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
        abort_unless($this->legalAnalysisService->isAnalysisFile($project, $file), 404);
    }
}
