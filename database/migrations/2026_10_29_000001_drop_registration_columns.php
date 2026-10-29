<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// As inscrições passam a viver somente em `project_registration_phases` (#636).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('data_registration');
        });

        Schema::table('openings', function (Blueprint $table) {
            $table->dropColumn('registration_data');
        });

        Schema::table('monitorings', function (Blueprint $table) {
            $table->dropColumn('data_registration');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->jsonb('data_registration')->nullable();
        });

        Schema::table('openings', function (Blueprint $table) {
            $table->jsonb('registration_data')->nullable();
        });

        Schema::table('monitorings', function (Blueprint $table) {
            $table->jsonb('data_registration')->nullable();
        });
    }
};
