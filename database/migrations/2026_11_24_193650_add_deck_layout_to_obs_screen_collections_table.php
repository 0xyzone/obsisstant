<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obs_screen_collections', function (Blueprint $table): void {
            $table->json('deck_layout')->nullable()->after('runtime_state');
        });
    }

    public function down(): void
    {
        Schema::table('obs_screen_collections', function (Blueprint $table): void {
            $table->dropColumn('deck_layout');
        });
    }
};
