<?php

namespace App\Http\Controllers;

use App\Enums\FileStatus;
use App\Enums\Role;
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

    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorizeLegalAnalysis($request);

        $grouped = $this->legalAnalysisService->groupedAnalysisFiles($project)
            ->map(fn (array $group) => [
                'key' => $group['key'],
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
        $this->authorizeLegalAnalysis($request);
        $this->assertFileBelongsToProject($project, $file);

        return SafeFileResponse::make($file, $request->boolean('download'));
    }

    public function updateFileStatus(Request $request, Project $project, File $file): JsonResponse
    {
        $this->assertFileBelongsToProject($project, $file);

        $data = $request->validate([
            'status' => ['required', Rule::enum(FileStatus::class)],
        ]);

        $this->legalAnalysisService->updateFileStatus($project, $file, FileStatus::from($data['status']), $request->user());

        return response()->json(['message' => 'Status atualizado com sucesso']);
    }

    private function authorizeLegalAnalysis(Request $request): void
    {
        abort_unless($request->user()?->hasAnyRole(Role::legalAnalysisRoles()) ?? false, 403);
    }

    private function assertFileBelongsToProject(Project $project, File $file): void
    {
        abort_unless($this->legalAnalysisService->isAnalysisFile($project, $file), 404);
    }
}
