<?php

namespace Database\Factories;

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
            'section'     => $this->faker->randomElement([
                DocumentImage::SECTION_HEADER,
                DocumentImage::SECTION_FOOTER,
            ]),
            'position'    => $this->faker->randomElement([
                DocumentImage::POSITION_LEFT,
                DocumentImage::POSITION_CENTER,
                DocumentImage::POSITION_RIGHT,
            ]),
            'path'        => 'images/' . $this->faker->uuid() . '.png',
        ];
    }
}
