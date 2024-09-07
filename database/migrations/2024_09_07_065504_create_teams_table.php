<?php

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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('short_name');
            $table->string('team_logo_path')->nullable();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->integer('p')->nullable();
            $table->integer('w')->nullable();
            $table->integer('l')->nullable();
            $table->integer('d')->nullable();
            $table->integer('f')->nullable();
            $table->integer('pts')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
