<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitorings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->index('created_by');

            $table->jsonb('technical_opinions')->default('[]');
            $table->text('observations')->nullable();
            $table->jsonb('data_registration')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitorings');
    }
};
