<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')
                ->unique()
                ->constrained()
                ->nullOnDelete();
            $table->date('creditor_requested_at')->nullable();
            $table->string('creditor_status')->nullable();
            $table->string('creditor_registration_number')->nullable();
            $table->date('communication_sent_at')->nullable();
            $table->text('contact_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_processes');
    }
};