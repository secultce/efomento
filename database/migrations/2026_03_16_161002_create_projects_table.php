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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();

            $table->string('registration_id')->unique();
            $table->string('number')->nullable();
            $table->foreignId('category_id')->nullable()->constrained();
            $table->foreignId('agent_id')->constrained();
            $table->foreignId('notice_id')->constrained();
            $table->unsignedTinyInteger('current_installment_cycle')->default(1);

            $table->timestamp('create_timestamp')->nullable();
            $table->timestamp('sent_timestamp')->nullable();

            $table->string('consolidated_result')->nullable();
            $table->jsonb('data_registration')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->index('created_by');
            $table->string('title_project')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
