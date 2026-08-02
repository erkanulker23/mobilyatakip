<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turkey_cities', function (Blueprint $table) {
            $table->unsignedSmallInteger('id')->primary();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('turkey_districts', function (Blueprint $table) {
            $table->unsignedMediumInteger('id')->primary();
            $table->unsignedSmallInteger('cityId');
            $table->string('name');
            $table->timestamps();

            $table->foreign('cityId')->references('id')->on('turkey_cities')->cascadeOnDelete();
            $table->index('cityId');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turkey_districts');
        Schema::dropIfExists('turkey_cities');
    }
};
