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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispatcher_id')->constrained('dispatchers')->onDelete('cascade');
            $table->string('ville');
            $table->date('date_rapport');
            $table->string('fichier_original_path')->nullable();
            $table->enum('statut', ['en_attente', 'traite', 'erreur'])->default('en_attente');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
