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
        Schema::table('dispatchers', function (Blueprint $table) {
            // Defaults to true: a dispatcher created directly by an admin via
            // the admin panel is trusted immediately. Only the public
            // self-registration endpoint explicitly sets this to false.
            $table->boolean('is_approved')->default(true)->after('password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dispatchers', function (Blueprint $table) {
            $table->dropColumn('is_approved');
        });
    }
};
