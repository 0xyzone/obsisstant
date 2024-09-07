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
        Schema::create('team_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('player_image_path')->nullable();
            $table->string('is_playing')->nullable();
            $table->string('is_mvp')->nullable();
            $table->integer('kills')->nullable();
            $table->integer('deaths')->nullable();
            $table->integer('assists')->nullable();
            $table->integer('net_worth')->nullable();
            $table->foreignId('hero_id')->nullable();
            $table->integer('hero_damage')->nullable();
            $table->integer('turret_damage')->nullable();
            $table->integer('damage_taken')->nullable();
            $table->string('fight_participation')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_players');
    }
};
