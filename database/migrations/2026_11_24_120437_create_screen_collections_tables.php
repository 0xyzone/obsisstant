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
        Schema::create('obs_screen_collections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('obs_studio_id')->nullable()->constrained('obs_studios')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('public_token', 64)->unique();
            $table->unsignedInteger('width')->default(1920);
            $table->unsignedInteger('height')->default(1080);
            $table->unsignedBigInteger('active_view_id')->nullable()->index();
            $table->json('theme')->nullable();
            $table->json('default_transition')->nullable();
            $table->json('runtime_state')->nullable();
            $table->string('obs_scene_name')->default('Obsisstant Stage');
            $table->string('obs_source_name')->default('Obsisstant - Unified Screen');
            $table->timestamps();
        });

        Schema::create('obs_screen_assets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('screen_collection_id')->nullable()->constrained('obs_screen_collections')->nullOnDelete();
            $table->string('name');
            $table->string('type', 24)->default('image');
            $table->string('disk', 32)->default('public');
            $table->string('path');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('original_filename')->nullable();
            $table->timestamps();
        });

        Schema::create('obs_screen_views', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('screen_collection_id')->constrained('obs_screen_collections')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->integer('sort_order')->default(0);
            $table->string('background_mode', 24)->default('theme');
            $table->string('background_color', 32)->nullable();
            $table->unsignedBigInteger('background_asset_id')->nullable();
            $table->json('theme_override')->nullable();
            $table->json('transition_override')->nullable();
            $table->json('layers')->nullable();
            $table->json('logic_rules')->nullable();
            $table->timestamps();

            $table->unique(['screen_collection_id', 'slug']);
        });

        Schema::table('obs_studios', function (Blueprint $table): void {
            $table->string('control_mode', 24)->default('obs')->after('media_storage_path');
            $table->foreignId('active_screen_collection_id')
                ->nullable()
                ->after('control_mode')
                ->constrained('obs_screen_collections')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('obs_studios', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('active_screen_collection_id');
            $table->dropColumn('control_mode');
        });

        Schema::dropIfExists('obs_screen_views');
        Schema::dropIfExists('obs_screen_assets');
        Schema::dropIfExists('obs_screen_collections');
    }
};
