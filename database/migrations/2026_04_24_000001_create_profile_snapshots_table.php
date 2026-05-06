<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_snapshots', function (Blueprint $table) {
            $table->id();
            $table->morphs('object');

            $table->string('gender')->nullable();
            $table->string('sexual_orientation')->nullable();
            $table->string('race')->nullable();
            $table->string('education')->nullable();
            $table->string('has_disability')->nullable();

            $table->string('phone')->nullable();
            $table->string('secondary_phone')->nullable();
            $table->string('email')->nullable();
            $table->string('secondary_email')->nullable();
            $table->date('birth_date')->nullable();

            $table->string('street')->nullable();
            $table->string('number')->nullable();
            $table->string('complement')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();

            $table->timestamp('recorded_at');
            $table->string('source');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_snapshots');
    }
};
