<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Project;
use App\Models\ProjectRegistrationPhase;
use App\Services\RegistrationPhaseFilesService;
use App\Support\SafeFileResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RegistrationPhaseFileController extends Controller
{
    public function __construct(private readonly RegistrationPhaseFilesService $phaseFiles) {}

    // Não usa scopeBindings: arquivo da fase 1 legada tem dono `project`, e scopeBindings daria 404.
    public function serveFile(Request $request, Project $project, ProjectRegistrationPhase $registrationPhase, File $file): StreamedResponse
    {
        abort_unless($registrationPhase->project_id === $project->id, 404);
        abort_unless($this->phaseFiles->ownedByPhaseOrProject($file, $registrationPhase, $project), 404);

        return SafeFileResponse::make($file, $request->boolean('download'));
    }
}
