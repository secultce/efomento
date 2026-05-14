<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileService
{
    private string $disk;

    public function __construct()
    {
        $this->disk = config('efomento.file_disk', 'public');
    }

    public function upload(Model $entity, UploadedFile $file, string $grp, array $options = []): File
    {
        $path = $this->buildPath($entity, $file);

        Storage::disk($this->disk)->putFileAs(
            dirname($path),
            $file,
            basename($path)
        );

        return File::create([
            'mime_type' => $file->getMimeType() ?? $file->getClientMimeType(),
            'name' => $file->getClientOriginalName(),
            'object_type' => $this->getMorphAlias($entity),
            'object_id' => $entity->getKey(),
            'grp' => $grp,
            'description' => $options['description'] ?? null,
            'path' => $path,
            'private' => (bool) ($options['private'] ?? false),
        ]);
    }

    public function delete(File $file): bool
    {
        if ($file->path) {
            Storage::disk($this->disk)->delete($file->path);
        }

        return (bool) $file->delete();
    }

    private function buildPath(Model $entity, UploadedFile $file): string
    {
        $alias = $this->getMorphAlias($entity);
        $id = $entity->getKey();
        $ext = $file->getClientOriginalExtension();
        $basename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $slug = Str::slug($basename);

        return "{$alias}/{$id}/{$slug}.{$ext}";
    }

    private function getMorphAlias(Model $entity): string
    {
        $map = Relation::getMorphMap();
        $class = get_class($entity);
        $alias = array_search($class, $map, true);

        return $alias !== false ? $alias : Str::snake(class_basename($entity));
    }
}
