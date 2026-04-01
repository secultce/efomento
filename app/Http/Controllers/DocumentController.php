<?php

namespace App\Http\Controllers;

use App\Http\Requests\Document\DocumentStoreRequest;
use App\Http\Requests\Document\DocumentUpdateRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use App\Services\Documents\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use InvalidArgumentException;

class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentService $documentService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $documents = $this->documentService->getByContext(
            noticeId:  $request->integer('notice_id') ?: null,
            projectId: $request->integer('project_id') ?: null,
            type:      $request->string('type')->toString() ?: null,
            phase:     $request->string('phase')->toString() ?: null,
        );

        return DocumentResource::collection($documents);
    }

    public function store(DocumentStoreRequest $request): DocumentResource|JsonResponse
    {
        try {
            $document = $this->documentService->create($request->validated(), $request->user()->id);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return DocumentResource::make($document->load('images'))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Document $document): DocumentResource
    {
        return DocumentResource::make($document->load('images'));
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
}