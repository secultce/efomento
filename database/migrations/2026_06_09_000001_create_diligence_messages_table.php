<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diligence_messages', function (Blueprint $table) {
            $table->id();
            $table->morphs('diligenceable');
            $table->string('direction');
            $table->string('from_email');
            $table->string('to_email');
            $table->string('subject');
            $table->text('body');
            $table->string('imap_message_id')->nullable()->unique();
            $table->string('in_reply_to')->nullable();
            $table->datetime('sent_at');
            $table->datetime('read_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diligence_messages');
    }
};
