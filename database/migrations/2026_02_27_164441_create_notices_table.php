<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\InstrumentType;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->string('nup')->nullable()->unique()->comment('Parent case number');
            $table->text('notice_url')->nullable()->comment('Public Notice Url'); 
            $table->string('external_id')->nullable()->comment('External Notice ID');
            $table->string('name')->comment('Notice Name');
            $table->decimal('total_notice_amount', 15, 2)->nullable()->comment('Total Notice Amount');
            $table->decimal('total_commitment_amount', 15, 2)->nullable()->comment('Total Commitment Amount');
            $table->integer('installments')->nullable()->comment('Number of Installments');
            $table->string('process_manager')->nullable()->comment('Process Manager Name');
            $table->string('process_manager_email')->nullable()->comment('Process Manager Email');
            $table->string('creditor_registration_nup')->nullable()->comment('Creditor Registration NUP');
            $table->date('creditor_registration_request_date')->nullable()->comment('Date of Creditor Registration Request');
            $table->string('budget_allocation_nup')->nullable()->comment('Budget Allocation NUP');
            $table->date('budget_allocation_request_date')->nullable()->comment('Date of Budget Allocation Request');
            $table->enum('instrument_type', InstrumentType::values())->nullable()->comment('Type of legal instrument');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->index('created_by');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notices');
    }
};
