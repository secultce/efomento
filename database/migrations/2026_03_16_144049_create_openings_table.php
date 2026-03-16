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
            $table->foreignId('project_id')->unique()->constrained()->cascadeOnDelete();
            
            // Dados de abertura
            $table->string('opening_nup')->nullable();
            $table->date('opening_date')->nullable();
            $table->string('agent_status')->nullable();
            $table->string('opened_by')->nullable();
            
            // Parcela principal
            $table->decimal('valor_repasse', 12, 2)->nullable();
            $table->string('valor_repasse_extenso')->nullable();
            
            // Dados bancários (críticos - colunas tipadas)
            $table->string('banco')->nullable();
            $table->string('tipo_conta')->nullable();
            $table->string('agencia')->nullable();
            $table->string('conta')->nullable();
            
            // Dados do fiscal (críticos - colunas tipadas)
            $table->string('fiscal_nome')->nullable();
            $table->string('fiscal_cpf')->nullable();
            $table->string('fiscal_matricula')->nullable();
            
            // JSONB flexíveis
            $table->jsonb('parcelas_adicionais')->nullable();
            $table->jsonb('certidao_eparcerias')->nullable();
            
            // Controle
            $table->boolean('is_draft')->default(true);
            $table->foreignId('responsible_user_id')->nullable()->constrained('users');
            $table->timestamp('submitted_at')->nullable();
            $table->enum('status', ['pendente', 'em_andamento', 'concluido', 'rejeitado'])->default('pendente');
            $table->foreignId('responsible_user_id')->nullable()->constrained('users');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('concluded_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['registration_id', 'is_draft']);
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
