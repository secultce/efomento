<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_registration_phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('registration_id');
            $table->unsignedBigInteger('opportunity_id')->nullable();
            $table->unsignedSmallInteger('phase_order');
            $table->boolean('is_first')->default(false);
            $table->unsignedSmallInteger('status')->nullable();
            $table->date('sent_at')->nullable();
            $table->timestamp('registration_from')->nullable();
            $table->timestamp('registration_to')->nullable();
            $table->jsonb('data')->nullable();
            $table->string('content_hash', 64)->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'registration_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_registration_phases');
    }
};
