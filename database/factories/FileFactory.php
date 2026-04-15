<?php

namespace Database\Factories;

use App\Models\File;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<File>
 */
class FileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mime_type'   => $this->faker->mimeType(),
            'name'        => $this->faker->word() . '.' . $this->faker->fileExtension(),
            'object_type' => Project::class,
            'object_id'   => Project::factory(),
            'grp'         => Str::random(10),
            'description' => $this->faker->optional()->sentence(),
            'path'        => $this->faker->optional()->filePath(),
            'private'     => $this->faker->boolean(30),
        ];
    }
}
