<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedBigInteger('latest_registration_id')->nullable();
            $table->unsignedSmallInteger('total_phases')->nullable();
            $table->string('summary_hash', 64)->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_changed_at')->nullable();
            $table->timestamp('next_check_at')->nullable();
            $table->timestamp('sync_closed_at')->nullable();

            $table->index(['sync_closed_at', 'next_check_at']);
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['sync_closed_at', 'next_check_at']);
            $table->dropColumn([
                'latest_registration_id',
                'total_phases',
                'summary_hash',
                'last_checked_at',
                'last_changed_at',
                'next_check_at',
                'sync_closed_at',
            ]);
        });
    }
};
