<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStageSlug;
use App\Http\Requests\Diligence\StoreDiligenceMessageRequest;
use App\Models\Project;
use App\Services\DiligenceableResolverRegistry;
use App\Services\DiligenceMessageService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

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
            ->with('creator:id,name')
            ->get()
            ->map(fn ($msg) => [
                'id' => $msg->id,
                'direction' => $msg->direction->value,
                'from_email' => $msg->from_email,
                'to_email' => $msg->to_email,
                'subject' => $msg->subject,
                'body' => $msg->body,
                'sent_at' => $msg->sent_at,
                'read_at' => $msg->read_at,
                'creator' => $msg->creator?->name,
            ]);

        return response()->json(['messages' => $messages]);
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
        );

        return response()->json(['message' => $message], Response::HTTP_CREATED);
    }
}
