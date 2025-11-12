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
        Schema::table('universities', function (Blueprint $table) {
            // Enrollment - filtreleme için ayrı kolonlar
            $table->unsignedInteger('enrollment_total')->nullable()->after('acceptance_rate');
            $table->unsignedInteger('enrollment_undergraduate')->nullable()->after('enrollment_total');
            $table->unsignedInteger('enrollment_graduate')->nullable()->after('enrollment_undergraduate');

            // Tuition - filtreleme için ayrı kolonlar
            $table->unsignedInteger('tuition_undergraduate')->nullable()->after('enrollment_graduate');
            $table->unsignedInteger('tuition_graduate')->nullable()->after('tuition_undergraduate');
            $table->unsignedInteger('tuition_international')->nullable()->after('tuition_graduate');
            $table->string('tuition_currency', 3)->default('USD')->after('tuition_international');

            // Requirements - filtreleme için ayrı kolonlar
            $table->decimal('requirement_gpa_min', 3, 2)->nullable()->after('tuition_currency');
            $table->unsignedSmallInteger('requirement_sat')->nullable()->after('requirement_gpa_min');
            $table->unsignedTinyInteger('requirement_act')->nullable()->after('requirement_sat');
            $table->unsignedTinyInteger('requirement_toefl')->nullable()->after('requirement_act');
            $table->decimal('requirement_ielts', 3, 1)->nullable()->after('requirement_toefl');

            // Index'ler - filtreleme performansı için
            $table->index('enrollment_total');
            $table->index('tuition_undergraduate');
            $table->index('tuition_graduate');
            $table->index('requirement_gpa_min');
            $table->index('requirement_sat');
            $table->index('requirement_act');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('universities', function (Blueprint $table) {
            $table->dropIndex(['enrollment_total']);
            $table->dropIndex(['tuition_undergraduate']);
            $table->dropIndex(['tuition_graduate']);
            $table->dropIndex(['requirement_gpa_min']);
            $table->dropIndex(['requirement_sat']);
            $table->dropIndex(['requirement_act']);

            $table->dropColumn([
                'enrollment_total',
                'enrollment_undergraduate',
                'enrollment_graduate',
                'tuition_undergraduate',
                'tuition_graduate',
                'tuition_international',
                'tuition_currency',
                'requirement_gpa_min',
                'requirement_sat',
                'requirement_act',
                'requirement_toefl',
                'requirement_ielts',
            ]);
        });
    }
};

