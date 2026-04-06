<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notice_id')->constrained()->nullOnDelete();
            $table->foreignId('project_id')->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('phase');
            $table->longText('body')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('notice_id');
            $table->index('project_id');
            $table->index(['type', 'phase']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
