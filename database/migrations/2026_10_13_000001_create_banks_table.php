<?php

use Database\Seeders\BankSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('ispb', 8)->unique();
            $table->string('code', 3)->nullable()->unique();
            $table->string('name');
            $table->string('short_name');
        });

        // Reference data is required even when demo/user seeding is disabled.
        (new BankSeeder)->run();

        Schema::table('openings', function (Blueprint $table) {
            $table->foreignId('bank_id')->nullable()->constrained()->restrictOnDelete();
            $table->dropColumn('bank');
        });
    }

    public function down(): void
    {
        Schema::table('openings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bank_id');
            $table->string('bank')->nullable();
        });

        Schema::dropIfExists('banks');
    }
};
