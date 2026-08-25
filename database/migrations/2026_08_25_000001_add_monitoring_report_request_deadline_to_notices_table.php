<?php

use App\Enums\MonitoringReportRequestDeadline;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->enum('monitoring_report_request_deadline', MonitoringReportRequestDeadline::values())
                ->default(MonitoringReportRequestDeadline::PNAB->value)
                ->comment('Rule used to calculate the monitoring report request deadline');
        });
    }

    public function down(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->dropColumn('monitoring_report_request_deadline');
        });
    }
};
