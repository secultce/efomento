<?php

namespace App\Services;

use App\Enums\CategoryType;
use App\Models\Category;

class CategoryService
{
    public function findOrCreate(
        ?string $name,
        CategoryType $type
    ): ?Category {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        return Category::firstOrCreate([
            'name' => $name,
            'type' => $type->value,
        ]);
    }

    public function findOrCreateProject(?string $name): ?Category
    {
        return $this->findOrCreate($name, CategoryType::PROJETO);
    }
}
