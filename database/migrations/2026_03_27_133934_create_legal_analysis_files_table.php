<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('legal_analysis_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_analysis_id')->constrained();
            $table->foreignId('file_id')->constrained();
            $table->string('status');
            $table->timestamps();
            $table->unique(['legal_analysis_id', 'file_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legal_analysis_files');
    }
};
