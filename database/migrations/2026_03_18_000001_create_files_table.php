<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->string('mime_type');
            $table->string('name');
            $table->string('object_type');
            $table->unsignedBigInteger('object_id');
            $table->string('grp', 32);
            $table->text('description')->nullable();
            $table->string('path', 1024)->nullable();
            $table->boolean('private')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['object_type', 'object_id']);
            $table->index('grp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
