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
