<?php

use App\Enums\Education;
use App\Enums\Gender;
use App\Enums\SexualOrientation;
use App\Enums\RaceColor;
use App\Enums\DisabilityType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();

            $table->string('registration_id')->unique();
            $table->string('number')->nullable();
            $table->foreignId('category_id')->constrained();
            $table->foreignId('agent_id')->constrained();
            $table->foreignId('notice_id')->constrained();

            $table->enum(
                'education_level',
                array_column(Education::cases(), 'value')
            )->nullable();

            $table->enum(
                'gender',
                array_column(Gender::cases(), 'value')
            )->nullable();

            $table->enum(
                'sexual_orientation',
                array_column(SexualOrientation::cases(), 'value')
            )->nullable();

            $table->enum(
                'race',
                array_column(RaceColor::cases(), 'value')
            )->nullable();

            $table->enum(
                'has_disability',
                array_column(DisabilityType::cases(), 'value')
            )->nullable();

            $table->timestamp('create_timestamp')->nullable();
            $table->timestamp('sent_timestamp')->nullable();

            $table->string('consolidated_result')->nullable();
            $table->jsonb('data_registration')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->index('created_by');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
