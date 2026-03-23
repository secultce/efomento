<?php

namespace App\Http\Controllers;

use App\Http\Requests\File\FileRequest;
use App\Http\Resources\FileResource;
use App\Models\File;
use App\Services\FileService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Response;

class FileController extends Controller
{
    public function __construct(private readonly FileService $fileService) {}

    public function store(FileRequest $request, string $objectType, int $objectId): FileResource
    {
        $map = Relation::getMorphMap();

        abort_unless(isset($map[$objectType]), 422, 'Tipo de entidade inválido.');

        /** @var \Illuminate\Database\Eloquent\Model $entity */
        $entity = $map[$objectType]::findOrFail($objectId);

        $file = $this->fileService->upload(
            $entity,
            $request->file('file'),
            $request->input('grp'),
            $request->only(['description', 'private']),
        );

        return new FileResource($file);
    }

    public function destroy(File $file): Response
    {
        $this->fileService->delete($file);

        return response()->noContent();
    }
}
