<?php

namespace Database\Factories;

use App\Enums\DocumentImagePosition;
use App\Enums\DocumentImageSection;
use App\Models\Document;
use App\Models\DocumentImage;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentImageFactory extends Factory
{
    protected $model = DocumentImage::class;

    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'section' => $this->faker->randomElement(DocumentImageSection::cases()),
            'position' => $this->faker->randomElement(DocumentImagePosition::cases()),
            'path' => 'images/'.$this->faker->uuid().'.png',
        ];
    }
}
