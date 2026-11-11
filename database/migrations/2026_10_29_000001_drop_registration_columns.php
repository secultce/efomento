<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// As inscrições passam a viver somente em `project_registration_phases` (#636).
// Antes de remover as colunas, os valores são copiados para `legacy_registration_backups`,
// para que nenhum dado se perca caso algum projeto ainda não tenha fases sincronizadas.
return new class extends Migration
{
    private const LEGACY_COLUMNS = [
        'projects' => 'data_registration',
        'openings' => 'registration_data',
        'monitorings' => 'data_registration',
    ];

    public function up(): void
    {
        Schema::create('legacy_registration_backups', function (Blueprint $table) {
            $table->id();
            $table->string('source_table');
            $table->unsignedBigInteger('source_id');
            $table->string('source_column');
            $table->jsonb('payload');
            $table->timestamps();

            $table->unique(['source_table', 'source_id', 'source_column']);
        });

        foreach (self::LEGACY_COLUMNS as $table => $column) {
            DB::table($table)
                ->whereNotNull($column)
                ->orderBy('id')
                ->chunkById(500, function ($rows) use ($table, $column) {
                    $now = now();

                    DB::table('legacy_registration_backups')->insertOrIgnore(
                        $rows->map(fn ($row) => [
                            'source_table' => $table,
                            'source_id' => $row->id,
                            'source_column' => $column,
                            'payload' => $row->{$column},
                            'created_at' => $now,
                            'updated_at' => $now,
                        ])->all()
                    );
                });
        }

        Schema::table('projects', fn (Blueprint $table) => $table->dropColumn('data_registration'));
        Schema::table('openings', fn (Blueprint $table) => $table->dropColumn('registration_data'));
        Schema::table('monitorings', fn (Blueprint $table) => $table->dropColumn('data_registration'));
    }

    public function down(): void
    {
        Schema::table('projects', fn (Blueprint $table) => $table->jsonb('data_registration')->nullable());
        Schema::table('openings', fn (Blueprint $table) => $table->jsonb('registration_data')->nullable());
        Schema::table('monitorings', fn (Blueprint $table) => $table->jsonb('data_registration')->nullable());

        foreach (self::LEGACY_COLUMNS as $table => $column) {
            DB::table('legacy_registration_backups')
                ->where('source_table', $table)
                ->where('source_column', $column)
                ->orderBy('id')
                ->chunkById(500, function ($backups) use ($table, $column) {
                    foreach ($backups as $backup) {
                        DB::table($table)
                            ->where('id', $backup->source_id)
                            ->update([$column => $backup->payload]);
                    }
                });
        }

        Schema::dropIfExists('legacy_registration_backups');
    }
};
