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
        Schema::create('report_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->onDelete('cascade');
            $table->enum('section', ['client', 'chauffeur', 'service']);
            $table->string('course_id');
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->onDelete('set null');
            $table->text('description');
            $table->text('remarque')->nullable();
            $table->string('categorie')->nullable();
            $table->enum('severite', ['faible', 'moyenne', 'haute'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_entries');
    }
};
