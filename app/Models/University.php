<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class University extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'short_name',
        'query',
        'location',
        'website',
        'founded',
        'type',
        'ranking',
        'acceptance_rate',
        'enrollment',
        'tuition',
        'deadlines',
        'requirements',
        'majors',
        'notable_majors',
        'scholarships',
        'housing',
        'campus_life',
        'contact',
        'faq',
        'overview',
        'meta_description',
        'region_code',
        'administrative_area',
        'locality',
        'place_title',
        'google_maps_uri',
        'gps_coordinates',
        'address',
        'phone',
        'open_state',
        'hours',
        'place_raw',
        // Filtreleme için ayrı kolonlar
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
    ];

    protected $casts = [
        'founded' => 'date',
        'ranking' => 'array',
        'enrollment' => 'array',
        'tuition' => 'array',
        'deadlines' => 'array',
        'requirements' => 'array',
        'majors' => 'array',
        'notable_majors' => 'array',
        'scholarships' => 'array',
        'housing' => 'array',
        'campus_life' => 'array',
        'contact' => 'array',
        'faq' => 'array',
        'gps_coordinates' => 'array',
        'hours' => 'array',
        'place_raw' => 'array',
        'requirement_gpa_min' => 'decimal:2',
        'requirement_ielts' => 'decimal:1',
    ];

    /**
     * Get the majors offered by this university.
     */
    public function majorsRelation(): BelongsToMany
    {
        return $this->belongsToMany(Major::class, 'university_major')
            ->withPivot('is_notable')
            ->withTimestamps();
    }

    /**
     * Get notable majors only.
     */
    public function notableMajorsRelation(): BelongsToMany
    {
        return $this->majorsRelation()->wherePivot('is_notable', true);
    }
}
