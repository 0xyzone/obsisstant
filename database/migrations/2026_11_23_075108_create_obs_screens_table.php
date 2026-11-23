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
        Schema::create('obs_screens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obs_studio_id')->nullable()->constrained('obs_studios')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('public_token', 64)->unique();
            $table->string('template_type')->default('lower_thirds');
            $table->unsignedInteger('width')->default(1920);
            $table->unsignedInteger('height')->default(1080);
            $table->json('theme')->nullable();
            $table->json('state')->nullable();
            $table->string('obs_scene_name')->nullable();
            $table->string('obs_source_name')->nullable();
            $table->boolean('is_live')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('obs_screens');
    }
};
