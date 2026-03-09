<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->string('nup')->nullable()->unique()->comment('Parent case number');
            $table->text('opportunity_url')->nullable()->comment('Public Opportunity Url'); 
            $table->string('external_id')->nullable()->comment('External Opportunity ID');
            $table->string('name')->comment('Opportunity Name');
            $table->decimal('total_opportunity_amount', 15, 2)->nullable()->comment('Total Opportunity Amount');
            $table->decimal('total_commitment_amount', 15, 2)->nullable()->comment('Total Commitment Amount');
            $table->integer('installments')->nullable()->comment('Number of Installments');
            $table->string('process_manager')->nullable()->comment('Process Manager Name');
            $table->string('process_manager_email')->nullable()->comment('Process Manager Email');
            $table->string('creditor_registration_nup')->nullable()->comment('Creditor Registration NUP');
            $table->date('creditor_registration_request_date')->nullable()->comment('Date of Creditor Registration Request');
            $table->string('budget_allocation_nup')->nullable()->comment('Budget Allocation NUP');
            $table->date('budget_allocation_request_date')->nullable()->comment('Date of Budget Allocation Request');
            $table->enum('instrument_type', [
                'TERMO DE EXECUÇÃO CULTURAL',
                'TERMO DE FOMENTO',
                'TERMO DE FOMENTO SIMPLIFICADO',
                'TERMO DE COLABORAÇÃO',
                'CONVÊNIO',
                'PREMIAÇÃO',
                'AQUISIÇÃO/CONTRATO',
                'PATROCÍNIO/CONTRATO'
            ])->nullable()->comment('Type of legal instrument');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};
