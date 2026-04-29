<?php

namespace App\Http\Controllers;

use App\Http\Requests\Document\DocumentStoreRequest;
use App\Http\Requests\Document\DocumentUpdateRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use App\Services\Documents\DocumentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

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

    public function store(DocumentStoreRequest $request): JsonResponse
    {
        $document = $this->documentService->create($request->validated(), $request->user()->id);

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

    public function download(Document $document): \Illuminate\Http\Response
    {
        $document->loadMissing('project.agent', 'project.notice', 'project.opening.activeSupervisor.user');

        $agentName = $document->project?->agent?->name ?? 'documento';
        $filename  = 'CI_' . str($agentName)->slug('_') . '_' . $document->created_at->format('Y-m-d') . '.pdf';

        $document->body = $this->replacePlaceholders($document);
        $pdf = Pdf::loadView('pdf.ci', ['document' => $document])
            ->setPaper('a4', 'portrait');

        return $pdf->download($filename);
    }

    public function downloadZip(Request $request): BinaryFileResponse
    {
        $projectIds = $request->validate(['project_ids' => 'required|array|min:1'])['project_ids'];

        $documents = Document::with(['project.agent', 'project.notice', 'project.opening.activeSupervisor.user'])
            ->whereIn('project_id', $projectIds)
            ->get();

        $tempFile = tempnam(sys_get_temp_dir(), 'docs_') . '.zip';
        $zip = new ZipArchive();
        $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($documents as $document) {
            $document->body = $this->replacePlaceholders($document);
            $pdf = Pdf::loadView('pdf.ci', ['document' => $document])->setPaper('a4', 'portrait');

            $agentName = $document->project?->agent?->name ?? 'documento';
            $filename = 'CI_' . str($agentName)->slug('_') . '_' . $document->created_at->format('Y-m-d') . '.pdf';

            $zip->addFromString($filename, $pdf->output());
        }

        $zip->close();

        return response()->download($tempFile, 'documentos.zip', [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend();
    }

    private function replacePlaceholders(Document $document): string
    {
        $replacements = [
            '[notice_name]'      => $document->project?->notice?->name,
            '[nup_mother]'       => $document->project?->notice?->nup,
            '[agent_name]'       => $document->project?->agent?->name,
            '[finality]'         => $document->project?->notice?->instrument_type,
            '[fiscal_matricula]' => $document->project?->opening?->activeSupervisor?->user?->registration_number ?? 'sem matrícula',
            '[fiscal_name]'      => $document->project?->opening?->activeSupervisor?->user?->name ?? 'sem fiscal',
            '[project_name]'     => $document->project?->title_project ?? 'sem projeto',
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $document->body
        );
    }
}
