<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_images', function (Blueprint $table) {
            $table->id();

            $table->foreignId('document_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('section');
            $table->string('position');
            $table->boolean('is_full_width')->default(false);
            $table->string('path', 1024);

            $table->timestamps();
            $table->softDeletes();

            $table->unique([
                'document_id',
                'section',
                'position',
                'deleted_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_images');
    }
};
