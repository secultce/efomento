<?php

namespace App\Support;

use App\Models\Document;
use Illuminate\Http\UploadedFile;

class FileUploader
{
    public static function handle(array $items): array
    {
        $processed = [];

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                $processed[$index] = $item;

                continue;
            }

            // Upload new file
            if (! empty($item['file']) && $item['file'] instanceof UploadedFile) {
                $item['path'] = $item['file']->store('documents', 'public');
                unset($item['file']);
            }

            // Restore existing file path if needed
            if (! empty($item['id']) && empty($item['_delete']) && empty($item['path'])) {
                $existing = Document::query()
                    ->find($item['id']);

                if ($existing) {
                    $item['path'] = $existing->path;
                }
            }

            $processed[$index] = $item;
        }

        return $processed;
    }
}
