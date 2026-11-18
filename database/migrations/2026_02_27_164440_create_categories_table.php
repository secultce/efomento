<?php

use App\Enums\CategoryType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', array_column(CategoryType::cases(), 'value'));
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['name', 'type'], 'categories_name_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
