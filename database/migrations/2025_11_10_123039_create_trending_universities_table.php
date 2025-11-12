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
        Schema::create('universities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('short_name')->nullable();
            $table->string('query')->nullable()->index();
            $table->string('location')->nullable();
            $table->string('website')->nullable();
            $table->date('founded')->nullable();
            $table->string('type')->nullable();
            $table->json('ranking')->nullable();
            $table->unsignedInteger('acceptance_rate')->nullable();
            $table->json('enrollment')->nullable();
            $table->json('tuition')->nullable();
            $table->json('deadlines')->nullable();
            $table->json('requirements')->nullable();
            $table->json('majors')->nullable();
            $table->json('notable_majors')->nullable();
            $table->json('scholarships')->nullable();
            $table->json('housing')->nullable();
            $table->json('campus_life')->nullable();
            $table->json('contact')->nullable();
            $table->json('faq')->nullable();
            $table->text('overview')->nullable();
            $table->string('region_code', 8)->nullable();
            $table->string('administrative_area')->nullable();
            $table->string('locality')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->string('place_title')->nullable();
            $table->text('google_maps_uri')->nullable();
            $table->json('gps_coordinates')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('open_state')->nullable();
            $table->json('hours')->nullable();
            $table->json('place_raw')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('universities');
    }
};
