<?php

use App\Providers\AppServiceProvider;
use Maatwebsite\Excel\ExcelServiceProvider;
use OwenIt\Auditing\AuditingServiceProvider;

return [
    AppServiceProvider::class,
    AuditingServiceProvider::class,
    ExcelServiceProvider::class,
];
