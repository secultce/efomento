<?php

use App\Enums\SexualOrientation;
use App\Enums\RaceColor;
use App\Enums\DisabilityType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('cpf')->unique();

            $table->string('director_position')->nullable();
            $table->string('director_email')->nullable();

            $table->string('phone')->nullable();
            $table->string('email');

            $table->date('birth_date')->nullable();

            $table->string('street')->nullable();
            $table->string('number')->nullable();
            $table->string('complement')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();

            $table->string('gender')->nullable();
            $table->string('education')->nullable();

            $table->enum(
                'sexual_orientation',
                array_column(SexualOrientation::cases(), 'value')
            )->nullable();

            $table->enum(
                'race_or_color',
                array_column(RaceColor::cases(), 'value')
            )->nullable();

            $table->enum(
                'has_disability',
                array_column(DisabilityType::cases(), 'value')
            )->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};