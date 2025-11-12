<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UniversityScore extends Model
{
    protected $fillable = [
        'university_id',
        'overall_grade',
        'ratings',
        'response_raw',
    ];

    protected $casts = [
        'ratings' => 'array',
        'response_raw' => 'array',
    ];

    /**
     * Get the university that owns the score
     */
    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }
}
