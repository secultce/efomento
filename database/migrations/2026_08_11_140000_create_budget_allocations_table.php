<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notice_id')->constrained()->cascadeOnDelete();
            $table->string('management_unit')->nullable();
            $table->string('work_program')->nullable();
            $table->string('objective')->nullable();
            $table->string('deliverable')->nullable();
            $table->string('budget_function')->nullable();
            $table->string('budget_subfunction')->nullable();
            $table->string('project_activity')->nullable();
            $table->string('expense_element')->nullable();
            $table->string('funding_source')->nullable();
            $table->string('mapp')->nullable();
            $table->string('finalistic_project')->nullable();
            $table->string('region_code')->nullable();
            $table->string('planning_macroregion')->nullable();
            $table->string('allocation_code')->nullable();
            $table->string('allocation_number')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->index('created_by');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_allocations');
    }
};
