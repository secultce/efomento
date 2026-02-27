<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('nup')->unique()->comment('Parent case number');
            $table->text('project_url')->nullable()->comment('Public Project Url'); 
            $table->string('external_id')->nullable()->comment('External Project ID');
            $table->string('name')->comment('Project Name');
            $table->decimal('total_project_amount', 15, 2)->nullable()->comment('Total Project Amount');
            $table->decimal('total_commitment_amount', 15, 2)->nullable()->comment('Total Commitment Amount');
            $table->integer('installments')->nullable()->comment('Number of Installments');
            $table->string('process_manager')->nullable()->comment('Process Manager Name');
            $table->string('process_manager_email')->nullable()->comment('Process Manager Email');
            $table->string('creditor_registration_nup')->nullable()->comment('Creditor Registration NUP');
            $table->date('creditor_registration_request_date')->nullable()->comment('Date of Creditor Registration Request');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
