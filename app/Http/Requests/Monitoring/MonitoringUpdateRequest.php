<?php

namespace App\Http\Requests\Monitoring;

class MonitoringUpdateRequest extends MonitoringStoreRequest
{
    public function authorize(): bool
    {
        return true;
    }
}
