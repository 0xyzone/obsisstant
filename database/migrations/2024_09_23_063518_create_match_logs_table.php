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
        Schema::create('match_logs', function (Blueprint $table) {
            $table->id();
            $table->string('match_title');
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_a')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('team_b')->constrained('teams')->cascadeOnDelete();
            $table->json('data');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('match_logs');
    }
};
