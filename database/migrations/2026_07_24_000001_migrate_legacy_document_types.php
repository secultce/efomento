<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            DB::table('documents')
                ->where('type', 'po')
                ->update(['type' => 'do']);

            DB::table('documents')
                ->where('type', 'd')
                ->update(['type' => 'dp']);
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            DB::table('documents')
                ->where('type', 'do')
                ->update(['type' => 'po']);

            DB::table('documents')
                ->where('type', 'dp')
                ->update(['type' => 'd']);
        });
    }
};
