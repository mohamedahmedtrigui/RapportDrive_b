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
        Schema::table('reports', function (Blueprint $table) {
            // Set when the owning dispatcher submits the report for AI
            // analysis (not when a manager manually re-triggers it). Drives
            // the "en cours de rédaction" vs "terminé" state in the UI.
            $table->timestamp('submitted_at')->nullable()->after('statut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('submitted_at');
        });
    }
};
