<?php

namespace App\Console\Commands;

use App\Services\PUMLGeneratorService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class GeneratePUML extends Command
{
    protected $signature = 'diagram:generate';

    protected $description = 'Generate ER and class diagrams for all models dynamically';

    public function handle()
    {
        $this->info('Scanning app/Models for Eloquent models...');

        $models = $this->getAllModels();

        $this->info(count($models).' models found.');
        $this->info('Generating PUML diagrams...');

        $service = new PUMLGeneratorService;
        $service->generate($models);

        $this->info('PUML diagrams generated successfully!');
    }

    protected function getAllModels(): array
    {
        $modelPath = app_path('Models');
        $files = File::allFiles($modelPath);

        $models = [];
        foreach ($files as $file) {
            $relative = $file->getRelativePathname();
            $class = 'App\\Models\\'.strtr(substr($relative, 0, strrpos($relative, '.')), '/', '\\');

            if (class_exists($class) && is_subclass_of($class, Model::class)) {
                $models[] = $class;
            }
        }

        return $models;
    }
}
