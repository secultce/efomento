<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('documents')->whereNull('project_id')->delete();

        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable(false)->change();
        });
    }
};
