<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Major extends Model
{
    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($major) {
            if (empty($major->slug)) {
                $major->slug = Str::slug($major->name);
            }
        });
    }

    /**
     * Get the universities that offer this major.
     */
    public function universities(): BelongsToMany
    {
        return $this->belongsToMany(University::class, 'university_major')
            ->withPivot('is_notable')
            ->withTimestamps();
    }
}

