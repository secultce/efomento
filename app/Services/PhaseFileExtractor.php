<?php

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * Extrai os arquivos dos campos `file` do `data` de uma fase da inscrição (seções e campos), no mesmo
 * formato normalizado que RegistrationFileExtractor entrega ao DownloadMapasProjectFileJob.
 */
class PhaseFileExtractor
{
    public function extract(array $phaseData): Collection
    {
        return collect(data_get($phaseData, 'sections', []))
            ->flatMap(fn (array $section) => data_get($section, 'fields', []))
            ->filter(fn ($field) => is_array($field) && data_get($field, 'type') === 'file')
            ->flatMap(function (array $field) {
                $files = data_get($field, 'value');

                return collect(is_array($files) ? $files : [])
                    ->filter(fn ($file) => is_array($file))
                    ->map(fn (array $file) => [
                        'external_id' => data_get($file, 'id'),
                        'url' => data_get($file, 'url'),
                        'name' => data_get($file, 'name'),
                        'title' => data_get($field, 'title'),
                        'grp' => 'rfc_'.data_get($field, 'id'),
                        'description' => data_get($field, 'description'),
                        'private' => true,
                    ]);
            })
            ->filter(fn (array $file) => filled($file['external_id']) && filled($file['url']) && filled($file['name']))
            ->unique('external_id')
            ->values();
    }
}
