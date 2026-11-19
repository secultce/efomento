<?php

namespace App\Support;

use App\Models\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serve arquivos enviados por terceiros. Só PDF e imagens raster abrem no navegador; qualquer outro tipo
 * (HTML, SVG...) sai como download, para não executar conteúdo do proponente na origem do e-Fomento.
 */
class SafeFileResponse
{
    private const INLINE_MIME_TYPES = [
        'application/pdf',
        'image/png',
        'image/jpeg',
        'image/gif',
        'image/webp',
    ];

    public static function make(File $file, bool $download = false): StreamedResponse
    {
        $disk = Storage::disk(config('efomento.file_disk', 'public'));

        abort_unless($file->path && $disk->exists($file->path), 404);

        $headers = ['X-Content-Type-Options' => 'nosniff'];

        if ($download || ! in_array(strtolower((string) $disk->mimeType($file->path)), self::INLINE_MIME_TYPES, true)) {
            return $disk->download($file->path, $file->name, $headers);
        }

        return $disk->response($file->path, $file->name, $headers);
    }
}
