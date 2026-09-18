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
        Schema::table('report_entries', function (Blueprint $table) {
            $table->dropColumn('zone');
        });

        Schema::table('report_entries', function (Blueprint $table) {
            $table->foreignId('zone_id')->nullable()->after('client_nom')->constrained('zones')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('zone_id');
        });

        Schema::table('report_entries', function (Blueprint $table) {
            $table->string('zone')->nullable()->after('client_nom');
        });
    }
};
