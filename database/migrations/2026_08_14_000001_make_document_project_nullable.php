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

        DB::statement(
            'CREATE UNIQUE INDEX documents_notice_level_unique '
            .'ON documents (notice_id, type, phase) '
            .'WHERE project_id IS NULL AND deleted_at IS NULL'
        );
    }

    public function down(): void
    {
        if (DB::table('documents')->whereNull('project_id')->exists()) {
            throw new RuntimeException(
                'Não é possível reverter enquanto existirem documentos vinculados diretamente a editais.'
            );
        }

        DB::statement('DROP INDEX IF EXISTS documents_notice_level_unique');

        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable(false)->change();
        });
    }
};
