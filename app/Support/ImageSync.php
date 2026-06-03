<?php

namespace App\Support;

class ImageSync
{
    public static function handle(array $items, callable $resolver): array
    {
        $processed = [];

        $relation = $resolver();

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                $processed[$index] = $item;

                continue;
            }

            // upload
            if (! empty($item['file'])) {
                $item['path'] = $item['file']->store('documents', 'public');
                unset($item['file']);
            }

            // restore
            if (
                ! empty($item['id']) &&
                empty($item['_delete']) &&
                empty($item['path'])
            ) {
                $existing = $relation->firstWhere('id', $item['id']);

                if ($existing) {
                    $item['path'] = $existing->path;
                }
            }

            $processed[$index] = $item;
        }

        return $processed;
    }
}
