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
        Schema::table('match_makings', function (Blueprint $table) {
            $table->string('teamA_match_point')->nullable()->after('team_a');
            $table->string('teamB_match_point')->nullable()->after('team_b');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('match_makings', function (Blueprint $table) {
            //
        });
    }
};
