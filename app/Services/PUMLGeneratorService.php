<?php
namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use ReflectionClass;

class PUMLGeneratorService
{
    protected string $diagramsPath;
    protected string $classesPath;

    public function __construct()
    {
        $this->diagramsPath = base_path('docs/diagrams/db');
        $this->classesPath = base_path('docs/diagrams/classes');

        File::ensureDirectoryExists($this->diagramsPath);
        File::ensureDirectoryExists($this->classesPath);
    }

    public function generate(array $models)
    {
        $erContent = $this->generateERHeader();
        $relationships = [];

        foreach ($models as $modelClass) {
            if (!class_exists($modelClass)) continue;
            $reflection = new ReflectionClass($modelClass);
            if (!$reflection->isSubclassOf(Model::class)) continue;

            /** @var Model $model */
            $model = new $modelClass();

            // Generate ER entity
            $erContent .= $this->generateEntity($model);

            // Detect relationships
            $relations = $this->getModelRelations($model);
            foreach ($relations as $name => $type) {
                $relatedModel = $type === MorphTo::class ? null : $model->$name()->getRelated();
                $from = class_basename($model);
                $to = $relatedModel ? class_basename($relatedModel) : 'Polymorphic';

                switch ($type) {
                    case BelongsTo::class:
                        $relationships[] = ['from' => $to, 'to' => $from, 'type' => '||--o{', 'label' => $name];
                        break;
                    case HasMany::class:
                        $relationships[] = ['from' => $from, 'to' => $to, 'type' => '||--o{', 'label' => $name];
                        break;
                    case HasOne::class:
                        $relationships[] = ['from' => $from, 'to' => $to, 'type' => '||--||', 'label' => $name];
                        break;
                    case BelongsToMany::class:
                        $relationships[] = ['from' => $from, 'to' => $to, 'type' => '}o--o{', 'label' => $name];
                        break;
                    case MorphOne::class:
                    case MorphMany::class:
                        $relationships[] = ['from' => $from, 'to' => $to, 'type' => '||--o{', 'label' => $name.' (morph)'];
                        break;
                    case MorphToMany::class:
                        $relationships[] = ['from' => $from, 'to' => $to, 'type' => '}o--o{', 'label' => $name.' (morph)'];
                        break;
                    case MorphTo::class:
                        $relationships[] = ['from' => $from, 'to' => $to, 'type' => 'o--||', 'label' => $name.' (morph)'];
                        break;
                }
            }

            // Generate class diagram
            $this->generateClassFile($model, $relations);
        }

        // Add relationships to ER
        $erContent .= $this->generateERRelationships($relationships);
        $erContent .= "@enduml\n";

        File::put($this->diagramsPath.'/models.puml', $erContent);
    }

    protected function generateERHeader(): string
    {
        return <<<EOT
@startuml e-Fomento — Diagrama de Relacionamento (ER)

!define TABLE(x) class x << (T,#FFAAAA) >>
!define PK(x) <u>x</u>
!define FK(x) <i>x</i>

skinparam linetype ortho
skinparam classBackgroundColor #F5F5F5
skinparam classBorderColor #37474F
skinparam classBorderThickness 1.5
skinparam classAttributeFontSize 11
skinparam classAttributeFontName Arial
skinparam arrowColor #37474F
skinparam arrowThickness 2
skinparam packageBackgroundColor #E8F5E9
skinparam packageBorderColor #2E7D32
skinparam packageFontColor #1B5E20
skinparam packageFontStyle bold

' ══════════════════════════════════════════
'  ENTIDADES
' ══════════════════════════════════════════

EOT;
    }

    protected function generateEntity(Model $model): string
    {
        $tableName = class_basename($model);
        $columns = $model->getConnection()->getSchemaBuilder()->getColumnListing($model->getTable());

        $content = "entity {$tableName} {\n";

        foreach ($columns as $column) {
            $type = $model->getCasts()[$column] ?? 'string';

            if ($column === $model->getKeyName()) {
                $content .= "  PK({$column}) : {$type}\n";
            } elseif (str_ends_with($column, '_id')) {
                $content .= "  FK({$column}) : {$type}\n";
            } else {
                $content .= "  {$column} : {$type}\n";
            }
        }

        $content .= "}\n\n";
        return $content;
    }

    protected function getModelRelations(Model $model): array
    {
        $relations = [];
        $class = new ReflectionClass($model);

        foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class !== get_class($model)) continue;
            if ($method->getNumberOfParameters() > 0) continue;

            try {
                $return = $method->invoke($model);
                if ($return instanceof Relation) {
                    $relations[$method->getName()] = get_class($return);
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return $relations;
    }

    protected function generateERRelationships(array $relations): string
    {
        $content = "\n' ══════════════════════════════════════════\n'  RELACIONAMENTOS\n' ══════════════════════════════════════════\n\n";

        foreach ($relations as $rel) {
            $label = $rel['label'] ? " : {$rel['label']}" : '';
            $content .= "{$rel['from']} {$rel['type']} {$rel['to']}{$label}\n";
        }

        return $content;
    }

    protected function generateClassFile(Model $model, array $relations)
    {
        $className = class_basename($model);
        $columns = $model->getConnection()->getSchemaBuilder()->getColumnListing($model->getTable());

        $content = "@startuml {$className}\n\nclass {$className} {\n";

        foreach ($columns as $column) {
            $type = $model->getCasts()[$column] ?? 'string';
            $content .= "    -{$column}: {$type}\n";
        }

        $content .= "    --\n";

        foreach ($relations as $name => $type) {
            $shortType = match ($type) {
                BelongsTo::class => 'BelongsTo',
                HasMany::class => 'HasMany',
                HasOne::class => 'HasOne',
                BelongsToMany::class => 'BelongsToMany',
                MorphOne::class => 'MorphOne',
                MorphMany::class => 'MorphMany',
                MorphToMany::class => 'MorphToMany',
                MorphTo::class => 'MorphTo',
                default => 'Relation',
            };
            $content .= "    +{$name}(): {$shortType}\n";
        }

        $content .= "}\n\n@enduml\n";

        File::put("{$this->classesPath}/{$className}.puml", $content);
    }
}