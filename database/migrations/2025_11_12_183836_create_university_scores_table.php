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
        Schema::create('university_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('university_id')->unique()->constrained()->onDelete('cascade');
            $table->string('overall_grade', 3)->nullable()->index(); // A+, B-, C+ gibi harf notları
            $table->json('ratings')->nullable(); // Detaylı puanlar (Academics, Value, Diversity, Campus, Athletics, Location)
            $table->json('response_raw')->nullable(); // OpenAI'dan gelen ham response
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('university_scores');
    }
};
