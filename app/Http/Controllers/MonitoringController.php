<?php

namespace App\Http\Controllers;

use App\Http\Requests\Monitoring\MonitoringStoreRequest;
use App\Http\Requests\Monitoring\MonitoringUpdateRequest;
use App\Models\File;
use App\Models\Monitoring;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MonitoringController extends Controller
{
    public function store(MonitoringStoreRequest $request, Project $project): RedirectResponse
    {
        $project->monitoring()->updateOrCreate(
            ['project_id' => $project->id],
            [
                ...$request->validated(),
                'created_by' => auth()->id(),
            ]
        );

        return back();
    }

    public function update(
        MonitoringUpdateRequest $request,
        Project $project,
        Monitoring $monitoring
    ): RedirectResponse {
        $monitoring->update($request->validated());

        return back();
    }

    public function files(Project $project): JsonResponse
    {
        $monitoring = $project->monitoring;

        if (! $monitoring) {
            return response()->json(['files' => []]);
        }

        $files = $monitoring->files()->get()->map(fn (File $file) => [
            'id' => $file->id,
            'name' => $file->name,
            'title' => $file->title ?? $file->name,
            'url' => route('projects.monitorings.files.serve', [$project, $file]),
        ])->values();

        return response()->json(['files' => $files]);
    }

    public function serveFile(Project $project, File $file, Request $request): StreamedResponse
    {
        abort_unless($file->object_type === 'monitoring' && $file->object_id === $project->monitoring?->id, 404);

        $disk = Storage::disk(config('efomento.file_disk', 'public'));

        abort_unless($disk->exists($file->path), 404);

        if ($request->boolean('download')) {
            return $disk->download($file->path, $file->name);
        }

        return $disk->response($file->path, $file->name);
    }
}
