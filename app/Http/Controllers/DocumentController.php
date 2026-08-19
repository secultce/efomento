<?php

namespace App\Http\Controllers;

use App\Enums\DocumentType;
use App\Enums\Role;
use App\Http\Requests\Document\DocumentStoreRequest;
use App\Http\Requests\Document\DocumentUpdateRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use App\Models\Project;
use App\Services\Documents\DocumentDocxService;
use App\Services\Documents\DocumentPdfService;
use App\Services\Documents\DocumentPlaceholderResolver;
use App\Services\Documents\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentService $documentService,
        private readonly DocumentPdfService $documentPdfService,
        private readonly DocumentDocxService $documentDocxService,
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

    public function destroy(Request $request, Document $document): JsonResponse
    {
        if ($document->type->isBudgetOpinion()) {
            abort_unless($request->user()?->hasAnyRole(Role::budgetRoles()) ?? false, 403);
        }

        $document->delete();

        return response()->json(null, 204);
    }

    public function download(Request $request, Document $document): Response|BinaryFileResponse
    {
        $format = $request->validate([
            'format' => ['sometimes', 'string', 'in:pdf,docx,docx_casa_civil'],
        ])['format'] ?? 'pdf';

        if (in_array($format, ['docx', 'docx_casa_civil'], true)) {
            $profile = $format === 'docx_casa_civil'
                ? DocumentDocxService::PROFILE_CASA_CIVIL
                : DocumentDocxService::PROFILE_STANDARD;

            return $this->documentDocxService->download($document, profile: $profile);
        }

        return $this->documentPdfService->download($document);
    }

    public function downloadZip(Request $request): BinaryFileResponse
    {
        $validated = $request->validate([
            'project_ids' => ['required', 'array', 'min:1'],
            'project_ids.*' => ['integer', 'distinct', Rule::exists(Project::class, 'id')],
            'type' => ['required', Rule::enum(DocumentType::class)],
            'format' => ['sometimes', 'string', 'in:pdf,docx,docx_casa_civil'],
        ]);
        $format = $validated['format'] ?? 'pdf';
        $path = match ($format) {
            'docx' => $this->documentDocxService->buildZip(
                $validated['project_ids'],
                $validated['type'],
            ),
            'docx_casa_civil' => $this->documentDocxService->buildZip(
                $validated['project_ids'],
                $validated['type'],
                DocumentDocxService::PROFILE_CASA_CIVIL,
            ),
            default => $this->documentPdfService->buildZip($validated['project_ids'], $validated['type']),
        };

        return response()->download($path, 'documentos.zip', [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend();
    }
}
