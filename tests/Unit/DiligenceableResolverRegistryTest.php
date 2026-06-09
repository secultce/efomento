<?php

namespace Tests\Unit;

use App\Contracts\DiligenceableResolver;
use App\Enums\ProjectStageSlug;
use App\Models\Monitoring;
use App\Models\Project;
use App\Services\DiligenceableResolverRegistry;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class DiligenceableResolverRegistryTest extends TestCase
{
    public function test_resolve_returns_model_for_registered_slug(): void
    {
        $project = new Project;
        $monitoring = new Monitoring;

        $resolver = new class($monitoring) implements DiligenceableResolver
        {
            public function __construct(private readonly Model $model) {}

            public function resolve(Project $project): Model
            {
                return $this->model;
            }
        };

        $registry = new DiligenceableResolverRegistry([
            ProjectStageSlug::MONITORAMENTO->value => $resolver,
        ]);

        $result = $registry->resolve($project, ProjectStageSlug::MONITORAMENTO);

        $this->assertSame($monitoring, $result);
    }

    public function test_resolve_aborts_with_404_for_unregistered_slug(): void
    {
        $registry = new DiligenceableResolverRegistry([]);

        try {
            $registry->resolve(new Project, ProjectStageSlug::ABERTURA);
            $this->fail('Expected HttpException was not thrown.');
        } catch (HttpException $e) {
            $this->assertSame(404, $e->getStatusCode());
        }
    }

    public function test_resolve_uses_correct_resolver_when_multiple_are_registered(): void
    {
        $project = new Project;
        $monitoringModel = new Monitoring;

        $monitoringResolver = new class($monitoringModel) implements DiligenceableResolver
        {
            public function __construct(private readonly Model $model) {}

            public function resolve(Project $project): Model
            {
                return $this->model;
            }
        };

        $otherModel = new Monitoring;
        $otherResolver = new class($otherModel) implements DiligenceableResolver
        {
            public function __construct(private readonly Model $model) {}

            public function resolve(Project $project): Model
            {
                return $this->model;
            }
        };

        $registry = new DiligenceableResolverRegistry([
            ProjectStageSlug::MONITORAMENTO->value => $monitoringResolver,
            ProjectStageSlug::ABERTURA->value => $otherResolver,
        ]);

        $this->assertSame($monitoringModel, $registry->resolve($project, ProjectStageSlug::MONITORAMENTO));
        $this->assertSame($otherModel, $registry->resolve($project, ProjectStageSlug::ABERTURA));
    }
}
