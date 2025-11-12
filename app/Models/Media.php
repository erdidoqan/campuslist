<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'disk',
        'directory',
        'filename',
        'extension',
        'mime_type',
        'size',
        'original_name',
        'hash_name',
        'path',
        'url',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $media) {
            if (empty($media->uuid)) {
                $media->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get university from meta
     *
     * @return University|null
     */
    public function university(): ?University
    {
        $universityId = $this->meta['university_id'] ?? null;

        if (! $universityId) {
            return null;
        }

        return University::find($universityId);
    }

    /**
     * Scope to filter by university ID
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $universityId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUniversity($query, int $universityId)
    {
        return $query->whereJsonContains('meta->university_id', $universityId);
    }
}

