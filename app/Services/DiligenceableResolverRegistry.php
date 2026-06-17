<?php

namespace App\Services;

use App\Contracts\DiligenceableResolver;
use App\Enums\ProjectStageSlug;
use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\Response;

class DiligenceableResolverRegistry
{
    /** @param array<string, DiligenceableResolver> $resolvers */
    public function __construct(private readonly array $resolvers) {}

    public function resolve(Project $project, ProjectStageSlug $slug): Model
    {
        $resolver = $this->resolvers[$slug->value] ?? null;

        abort_unless($resolver !== null, Response::HTTP_NOT_FOUND);

        $diligenceable = $resolver->resolve($project);

        abort_unless($diligenceable !== null, Response::HTTP_NOT_FOUND);

        return $diligenceable;
    }
}
