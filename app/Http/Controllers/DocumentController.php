<?php

namespace App\Http\Controllers;

use App\Http\Requests\Document\DocumentStoreRequest;
use App\Http\Requests\Document\DocumentUpdateRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use App\Services\Documents\DocumentPdfService;
use App\Services\Documents\DocumentPlaceholderResolver;
use App\Services\Documents\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentService $documentService,
        private readonly DocumentPdfService $documentPdfService,
        private readonly DocumentPlaceholderResolver $placeholderResolver,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $documents = $this->documentService->getByContext(
            noticeId: $request->integer('notice_id') ?: null,
            projectId: $request->integer('project_id') ?: null,
            type: $request->string('type')->toString() ?: null,
            phase: $request->string('phase')->toString() ?: null,
        );

        return DocumentResource::collection($documents);
    }

    public function store(DocumentStoreRequest $request): JsonResponse
    {
        $document = $this->documentService->create($request->validated(), $request->user()->id);

        return DocumentResource::make($document->load('images'))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Document $document): DocumentResource
    {
        $document->load([
            'images',
            'project.agent.latestSnapshot',
            'project.notice',
            'project.opening.principalSupervisor.user',
        ]);

        return DocumentResource::make($this->placeholderResolver->prepare($document));
    }

    public function update(DocumentUpdateRequest $request, Document $document): DocumentResource
    {
        $document = $this->documentService->update($document, $request->validated());

        return DocumentResource::make($document->load('images'));
    }

    public function destroy(Document $document): JsonResponse
    {
        $document->delete();

        return response()->json(null, 204);
    }

    public function download(Document $document): Response
    {
        return $this->documentPdfService->download($document);
    }

    public function downloadZip(Request $request): BinaryFileResponse
    {
        $projectIds = $request->validate(['project_ids' => 'required|array|min:1'])['project_ids'];
        $type = $request->validate(['type' => 'required|string'])['type'];

        return response()->download($this->documentPdfService->buildZip($projectIds, $type), 'documentos.zip', [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend();
    }
}
