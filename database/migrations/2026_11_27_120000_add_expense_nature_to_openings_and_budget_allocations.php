<?php

use App\Enums\ExpenseNature;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('openings', function (Blueprint $table) {
            $table->string('expense_nature')->nullable();
        });

        Schema::table('budget_allocations', function (Blueprint $table) {
            $table->string('expense_nature')->nullable();
            $table->index(['notice_id', 'expense_nature']);
        });

        DB::table('budget_allocations')
            ->whereNotNull('expense_element')
            ->orderBy('id')
            ->select(['id', 'expense_element'])
            ->chunkById(500, function ($allocations) {
                foreach ($allocations as $allocation) {
                    $nature = ExpenseNature::fromExpenseElement($allocation->expense_element);

                    if ($nature) {
                        DB::table('budget_allocations')
                            ->where('id', $allocation->id)
                            ->update(['expense_nature' => $nature->value]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('budget_allocations', function (Blueprint $table) {
            $table->dropIndex(['notice_id', 'expense_nature']);
            $table->dropColumn('expense_nature');
        });

        Schema::table('openings', function (Blueprint $table) {
            $table->dropColumn('expense_nature');
        });
    }
};
