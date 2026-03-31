<?php

namespace App\Http\Controllers;

use App\Http\Requests\Document\DocumentStoreRequest;
use App\Http\Requests\Document\DocumentUpdateRequest;
use App\Models\Document;
use App\Models\User;
use App\Services\Documents\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentService $documentService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $documents = $this->documentService->getByContext(
            noticeId:  $request->integer('notice_id') ?: null,
            projectId: $request->integer('project_id') ?: null,
            type:      $request->string('type')->toString() ?: null,
            phase:     $request->string('phase')->toString() ?: null,
        );

        return response()->json($documents);
    }

    public function store(DocumentStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $document = $this->documentService->create($validated, User::find(214)?->id ?? auth()->id());
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($document->load('images'), 201);
    }

    public function show(Document $document): JsonResponse
    {
        return response()->json($document->load('images'));
    }

    public function update(DocumentUpdateRequest $request, Document $document): JsonResponse
    {
        $document = $this->documentService->update($document, $request->validated());

        return response()->json($document->load('images'));
    }

    public function destroy(Document $document): JsonResponse
    {
        $document->delete();

        return response()->json(null, 204);
    }
}
