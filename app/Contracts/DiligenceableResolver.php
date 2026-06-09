<?php

namespace App\Contracts;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;

interface DiligenceableResolver
{
    public function resolve(Project $project): Model;
}
