<?php

namespace App\Contracts;

use App\Models\Project;

interface StageValidatorInterface
{
    public function ensureCanAdvance(Project $project): void;
}
