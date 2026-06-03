<?php

namespace App\Http\Controllers;

use App\Http\Requests\Formalization\FormalizationStoreRequest;
use App\Http\Requests\Formalization\FormalizationUpdateRequest;
use App\Models\File;
use App\Models\Formalization;
use App\Models\Project;
use App\Services\FormalizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FormalizationController extends Controller
{
    public function __construct(
        private FormalizationService $formalizationService
    ) {}

    public function store(FormalizationStoreRequest $request, Project $project): RedirectResponse
    {
        $data = $request->validated();

        unset($data['official_gazette_file']);

        $formalization = $project->formalizations()->updateOrCreate(
            ['project_id' => $project->id],
            [
                ...$data,
                'created_by' => auth()->id(),
            ]
        );

        if ($request->hasFile('official_gazette_file')) {
            $this->formalizationService->saveOfficialGazetteFile(
                $request->file('official_gazette_file'),
                $project,
                $formalization
            );
        }

        return back();
    }

    public function update(
        FormalizationUpdateRequest $request,
        Project $project,
        Formalization $formalization
    ): RedirectResponse {
        $data = $request->validated();

        unset($data['official_gazette_file']);

        $formalization->update($data);

        if ($request->hasFile('official_gazette_file')) {
            $this->formalizationService->saveOfficialGazetteFile(
                $request->file('official_gazette_file'),
                $project,
                $formalization
            );
        }

        return back();
    }

    public function showFile(Project $project, Formalization $formalization, File $file): StreamedResponse
    {
        $disk = config('filesystems.default', 'local');

        return Storage::disk($disk)->response(
            $file->path,
            $file->name,
            [
                'Content-Type' => $file->mime_type,
                'Content-Disposition' => 'inline; filename="'.$file->name.'"',
            ]
        );
    }

    public function downloadFile(Project $project, Formalization $formalization, File $file)
    {
        $disk = config('filesystems.default', 'local');

        return Storage::disk($disk)->download($file->path, $file->name);
    }

    public function destroyFile(Project $project, Formalization $formalization, File $file): RedirectResponse
    {
        $this->formalizationService->deleteFile($file);

        return back();
    }
}
