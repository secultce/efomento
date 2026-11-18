<?php

namespace App\Services;

class AuditService
{
    public function getAudits($model)
    {
        return $model->audits()
            ->with('user')
            ->latest()
            ->get();
    }
}
