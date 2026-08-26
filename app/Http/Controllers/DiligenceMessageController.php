<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStageSlug;
use App\Http\Requests\Diligence\StoreDiligenceMessageRequest;
use App\Http\Resources\DiligenceMessageResource;
use App\Models\DiligenceMessage;
use App\Models\File;
use App\Models\Project;
use App\Services\DiligenceableResolverRegistry;
use App\Services\DiligenceMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DiligenceMessageController extends Controller
{
    public function __construct(
        private readonly DiligenceMessageService $service,
        private readonly DiligenceableResolverRegistry $registry,
    ) {}

    public function index(Project $project, string $stage): JsonResponse
    {
        $diligenceable = $this->registry->resolve($project, ProjectStageSlug::from($stage));

        $messages = $diligenceable->diligenceMessages()
            ->with(['creator:id,name', 'attachments'])
            ->get();

        return response()->json([
            'messages' => DiligenceMessageResource::collection($messages),
        ]);
    }

    public function store(StoreDiligenceMessageRequest $request, Project $project, string $stage): JsonResponse
    {
        $diligenceable = $this->registry->resolve($project, ProjectStageSlug::from($stage));

        $message = $this->service->send(
            diligenceable: $diligenceable,
            subject: $request->input('subject'),
            body: $request->input('body'),
            toEmail: $request->input('to_email'),
            sender: $request->user(),
            ccEmail: $project->agent?->latestSnapshot?->secondary_email,
        );

        $message->load(['creator:id,name', 'attachments']);

        return response()->json([
            'message' => (new DiligenceMessageResource($message))->resolve($request),
        ], Response::HTTP_CREATED);
    }

    public function downloadAttachment(
        Project $project,
        string $stage,
        DiligenceMessage $message,
        File $file,
    ): StreamedResponse {
        $diligenceable = $this->registry->resolve($project, ProjectStageSlug::from($stage));

        abort_unless($message->diligenceable->is($diligenceable), 404);
        abort_unless($message->attachments()->whereKey($file->id)->exists(), 404);

        $disk = Storage::disk(config('efomento.file_disk', 'public'));

        abort_unless($file->path && $disk->exists($file->path), 404);

        return $disk->download($file->path, $file->name, [
            'Content-Type' => $file->mime_type,
        ]);
    }
}
