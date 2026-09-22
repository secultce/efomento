<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->nullableMorphs('related');
            $table->string('recipient_email');
            $table->string('recipient_name')->nullable();
            $table->string('mail_class');
            $table->string('event_type')->index();
            $table->string('subject');
            $table->string('status')->default('queued')->index();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('deduplication_key')->nullable()->unique();
            $table->timestamps();
            $table->index('agent_id');
            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_email_logs');
    }
};
