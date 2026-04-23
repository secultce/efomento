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
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained();
            $table->foreignId('created_by')->constrained('users');

            // Datas
            $table->date('processing_date_for_codip')->nullable(); // Data de tramitação para a CODIP
            $table->date('processing_date_for_coafi')->nullable(); // Data de tramitação para a Coafi
            // Quadro de detalhamento de despesas
            $table->date('date_of_expense_breakdown_table')->nullable(); // Data do quadro de detalhamento de despesas

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
        Schema::dropIfExists('budgets');
    }
};
