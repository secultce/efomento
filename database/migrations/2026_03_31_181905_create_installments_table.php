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
        Schema::create('installments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('budget_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('amount', 10, 2)->nullable();
            $table->date('request_date')->nullable();

            $table->text('observations')->nullable();

            $table->integer('installment_number');

            $table->string('commitment_number')->nullable();
            $table->date('commitment_date')->nullable();

            $table->string('settlement_number')->nullable();
            $table->date('settlement_date')->nullable();
            $table->decimal('settlement_amount', 10, 2)->nullable();

            $table->string('payment_order_number')->nullable();
            $table->decimal('payment_amount', 10, 2)->nullable();
            $table->date('payment_date')->nullable();

            $table->string('full_source')->nullable();
            $table->string('expense_nature')->nullable();

            $table->string('process_number')->nullable();

            $table->string('creditor')->nullable();
            $table->string('creditor_name')->nullable();
            $table->string('retention_creditor')->nullable();

            $table->string('origin_bank_domicile')->nullable();

            $table->decimal('committed_amount', 10, 2)->nullable();

            $table->text('remarks')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['budget_id', 'installment_number']);

            $table->index('created_by');
            $table->index('updated_by');

            $table->index('installment_number');
            $table->index('settlement_number');
            $table->index('payment_order_number');

            $table->index('process_number');
            $table->index('creditor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installments');
    }
};
