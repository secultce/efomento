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
        Schema::create('monitorings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained();
            $table->foreignId('created_by')->constrained('users');
            $table->index('created_by');

            $table->date('effective_date_of_the_instrument')->nullable(); // Data de início de vigência do instrumento
            $table->date('expiration_date_of_the_instrument')->nullable(); // Data de término de vigência do instrumento
            $table->date('deadline_for_completing_report')->nullable(); // Prazo de preenchimento de relatório de monitoramento
            $table->date('deadline_for_analysis_and_issuance_of_the_opinion')->nullable(); // Prazo para análise e emissão do parecer de monitoramento
            $table->jsonb('technical_opinions')->default('[]'); // Pareceres técnicos: [{suite_number, processing_date}]
            $table->date('date_of_notification_to_the_agent')->nullable(); // Data da notificação ao agente
            $table->text('observations')->nullable();

            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitorings');
    }
};
