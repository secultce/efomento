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
        Schema::create('openings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')
                ->unique()
                ->constrained()
                ->nullOnDelete();
            
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('supervisor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            
            $table->string('opening_nup')->nullable();
            $table->date('opening_date')->nullable();
            $table->string('agent_status')->nullable();
            $table->string('opened_by')->nullable();

            $table->string('bank')->nullable();
            $table->string('account_type')->nullable();
            $table->string('branch')->nullable();
            $table->string('account')->nullable();

            $table->boolean('is_draft')->default(true);

            $table->enum('status', [
                'pendente',
                'em_andamento',
                'concluido',
                'rejeitado'
            ])->default('pendente');
            $table->dateTime('certificate_date')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('concluded_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('openings');
    }
};
