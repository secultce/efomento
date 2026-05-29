<?php

namespace App\Http\Controllers;

use App\Http\Requests\Formalization\FormalizationStoreRequest;
use App\Http\Requests\Formalization\FormalizationUpdateRequest;
use App\Models\File;
use App\Models\Formalization;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class FormalizationController extends Controller
{
    private const OFFICIAL_GAZETTE_FILE_GROUP = 'official_gazette';

    public function store(FormalizationStoreRequest $request, Project $project): RedirectResponse
    {
        $data = $request->validated();

        unset($data['official_gazette_file']);

        $formalization = $project->formalization()->updateOrCreate(
            ['project_id' => $project->id],
            [
                ...$data,
                'created_by' => auth()->id(),
            ]
        );

        $this->saveOfficialGazetteFile($request, $project, $formalization);

        return back();
    }

    public function update(
        FormalizationUpdateRequest $request,
        Project $project,
        Formalization $formalization
    ): RedirectResponse {
        abort_unless($formalization->project_id === $project->id, 404);

        $data = $request->validated();

        unset($data['official_gazette_file']);

        $formalization->update($data);

        $this->saveOfficialGazetteFile($request, $project, $formalization);

        return back();
    }

    private function saveOfficialGazetteFile(
        FormalizationStoreRequest|FormalizationUpdateRequest $request,
        Project $project,
        Formalization $formalization
    ): void {
        if (! $request->hasFile('official_gazette_file')) {
            return;
        }

        $uploadedFile = $request->file('official_gazette_file');

        $disk = 'local';

        $formalization->files()
            ->where('grp', self::OFFICIAL_GAZETTE_FILE_GROUP)
            ->get()
            ->each(function ($file) use ($disk) {
                if ($file->path) {
                    Storage::disk($disk)->delete($file->path);
                }

                $file->delete();
            });

        $path = $uploadedFile->store(
            "projects/{$project->id}/formalizations/{$formalization->id}/official-gazette",
            $disk
        );

        $formalization->files()->create([
            'mime_type' => $uploadedFile->getClientMimeType(),
            'name' => $uploadedFile->getClientOriginalName(),
            'source' => 'upload',
            'grp' => self::OFFICIAL_GAZETTE_FILE_GROUP,
            'title' => 'Anexo do documento do Diário Oficial do Estado',
            'description' => null,
            'path' => $path,
            'private' => true,
        ]);
    }

    public function showFile(Project $project, Formalization $formalization, File $file): Response
    {
        $this->ensureFileBelongsToFormalization($project, $formalization, $file);

        abort_unless($file->path && Storage::disk('local')->exists($file->path), 404);

        return response(Storage::disk('local')->get($file->path), 200, [
            'Content-Type' => $file->mime_type,
            'Content-Disposition' => 'inline; filename="'.$file->name.'"',
        ]);
    }

    public function downloadFile(Project $project, Formalization $formalization, File $file)
    {
        $this->ensureFileBelongsToFormalization($project, $formalization, $file);

        abort_unless($file->path && Storage::disk('local')->exists($file->path), 404);

        return Storage::disk('local')->download($file->path, $file->name);
    }

    public function destroyFile(Project $project, Formalization $formalization, File $file): RedirectResponse
    {
        $this->ensureFileBelongsToFormalization($project, $formalization, $file);

        if ($file->path) {
            Storage::disk('local')->delete($file->path);
        }

        $file->delete();

        return back();
    }

    private function ensureFileBelongsToFormalization(Project $project, Formalization $formalization, File $file): void
    {
        abort_unless($formalization->project_id === $project->id, 404);

        abort_unless(
            $file->object_type === Formalization::class &&
                (int) $file->object_id === (int) $formalization->id,
            404
        );
    }
}
