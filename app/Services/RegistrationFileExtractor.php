<?php

namespace App\Services;

use Illuminate\Support\Collection;

class RegistrationFileExtractor
{
    public function extract(array $files, array $fileConfigurations): Collection
    {
        $configurationsByGroup = collect($fileConfigurations)
            ->keyBy(fn (array $configuration) => data_get($configuration, 'groupName'));

        return collect($files)
            ->reject(fn (array $file, string $group) => $group === 'zipArchive')
            ->map(function (array $file, string $group) use ($configurationsByGroup) {
                $configuration = $configurationsByGroup->get($group, []);

                return [
                    'external_id' => data_get($file, 'id'),
                    'url' => data_get($file, 'url'),
                    'name' => data_get($file, 'name'),
                    'title' => data_get($file, 'title') ?: data_get($configuration, 'title'),
                    'grp' => $group,
                    'description' => data_get($configuration, 'description'),
                    'private' => true,
                ];
            })
            ->filter(fn (array $file) => filled($file['external_id']) && filled($file['url']) && filled($file['name']))
            ->values();
    }
}
