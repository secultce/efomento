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

            $table->foreignId('budget_id')->constrained();

            $table->decimal('amount', 10, 2);
            $table->date('request_date');

            $table->text('justification')->nullable();
            $table->text('observations')->nullable();

            $table->integer('installment_number');

            $table->string('commitment_number')->nullable();
            $table->date('commitment_date')->nullable();

            $table->string('settlement_number')->nullable();

            $table->string('payment_order_number')->nullable();

            $table->decimal('payment_amount', 10, 2)->nullable();
            $table->date('payment_date')->nullable();

            $table->text('remarks')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->index('created_by');

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->index('updated_by');

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['budget_id', 'installment_number']);
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
