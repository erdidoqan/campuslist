<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

class Major extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'title',
        'meta_description',
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
            
            if (empty($major->title)) {
                $major->title = $major->generateTitle();
            }
            
            if (empty($major->meta_description)) {
                $major->meta_description = $major->generateMetaDescription();
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

    /**
     * Generate title for the major
     *
     * @return string
     */
    public function generateTitle(): string
    {
        $templates = Lang::get('majors.titles', []);

        if (is_string($templates)) {
            $templates = [$templates];
        }

        if (! is_array($templates) || empty($templates)) {
            return $this->name . ' Major - Programs & Universities';
        }

        $template = Arr::random($templates);

        return __($template, ['name' => $this->name]);
    }

    /**
     * Generate meta description for the major
     *
     * @return string
     */
    public function generateMetaDescription(): string
    {
        $templates = Lang::get('majors.meta_descriptions', []);

        if (is_string($templates)) {
            $templates = [$templates];
        }

        if (! is_array($templates) || empty($templates)) {
            return "Explore {$this->name} major programs, find universities offering this degree, and learn about career opportunities.";
        }

        $template = Arr::random($templates);

        $description = __($template, ['name' => $this->name]);

        return Str::limit(trim($description), 300, '');
    }
}

