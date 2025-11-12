<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\University;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    /**
     * List media with filtering and pagination
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Media::query();

        // Filter by university ID
        if ($request->has('university_id')) {
            $query->forUniversity((int) $request->get('university_id'));
        }

        // Filter by university slug
        if ($request->has('university_slug')) {
            $university = University::where('slug', $request->get('university_slug'))->first();
            if ($university) {
                $query->forUniversity($university->id);
            } else {
                // Return empty result if university not found
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'meta' => [
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => (int) $request->get('per_page', 15),
                        'total' => 0,
                        'from' => null,
                        'to' => null,
                    ],
                ]);
            }
        }

        // Filter by disk
        if ($request->has('disk')) {
            $query->where('disk', $request->get('disk'));
        }

        // Filter by mime type
        if ($request->has('mime_type')) {
            $mimeType = $request->get('mime_type');
            if (str_contains($mimeType, '/')) {
                // Exact match
                $query->where('mime_type', $mimeType);
            } else {
                // Type only (e.g., "image", "video")
                $query->where('mime_type', 'like', "{$mimeType}/%");
            }
        }

        // Search in filename or original_name
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('filename', 'like', "%{$search}%")
                    ->orWhere('original_name', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $allowedSortFields = ['created_at', 'updated_at', 'size', 'filename', 'mime_type'];

        if (in_array($sortBy, $allowedSortFields, true)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Pagination
        $perPage = min((int) $request->get('per_page', 15), 100);
        $media = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $media->map(function ($item) {
                return $this->formatMedia($item);
            }),
            'meta' => [
                'current_page' => $media->currentPage(),
                'last_page' => $media->lastPage(),
                'per_page' => $media->perPage(),
                'total' => $media->total(),
                'from' => $media->firstItem(),
                'to' => $media->lastItem(),
            ],
        ]);
    }

    /**
     * Show media by ID
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $media = Media::find($id);

        if (! $media) {
            return response()->json([
                'success' => false,
                'message' => 'Medya bulunamadı.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatMedia($media),
        ]);
    }

    /**
     * Get media for a specific university
     *
     * @param  int  $universityId
     * @return JsonResponse
     */
    public function forUniversity(int $universityId): JsonResponse
    {
        $university = University::find($universityId);

        if (! $university) {
            return response()->json([
                'success' => false,
                'message' => 'Üniversite bulunamadı.',
            ], 404);
        }

        $media = Media::forUniversity($universityId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'university' => [
                    'id' => $university->id,
                    'name' => $university->name,
                    'slug' => $university->slug,
                ],
                'media' => $media->map(function ($item) {
                    return $this->formatMedia($item);
                }),
                'count' => $media->count(),
            ],
        ]);
    }

    /**
     * Format media data for API response
     *
     * @param  Media  $media
     * @return array<string, mixed>
     */
    protected function formatMedia(Media $media): array
    {
        $universityId = $media->meta['university_id'] ?? null;
        $university = $universityId ? University::find($universityId) : null;

        // Generate Glide URLs for different sizes
        $glideUrls = $this->generateGlideUrls($media);

        return [
            'id' => $media->id,
            'uuid' => $media->uuid,
            'disk' => $media->disk,
            'filename' => $media->filename,
            'original_name' => $media->original_name,
            'extension' => $media->extension,
            'mime_type' => $media->mime_type,
            'size' => $media->size,
            'size_human' => $this->formatBytes($media->size),
            'path' => $media->path,
            'url' => $media->url,
            'glide_urls' => $glideUrls,
            'directory' => $media->directory,
            'meta' => $media->meta,
            'university' => $university ? [
                'id' => $university->id,
                'name' => $university->name,
                'slug' => $university->slug,
            ] : null,
            'created_at' => $media->created_at?->toISOString(),
            'updated_at' => $media->updated_at?->toISOString(),
        ];
    }

    /**
     * Format bytes to human readable format
     *
     * @param  int|null  $bytes
     * @return string
     */
    protected function formatBytes(?int $bytes): string
    {
        if ($bytes === null || $bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes, 1024));

        return round($bytes / pow(1024, $i), 2).' '.$units[$i];
    }

    /**
     * Generate Glide URLs for different sizes
     *
     * @param  Media  $media
     * @return array<string, string>
     */
    protected function generateGlideUrls(Media $media): array
    {
        // Only generate Glide URLs for images
        if (! str_starts_with($media->mime_type ?? '', 'image/')) {
            return [];
        }

        $baseUrl = route('glide', ['path' => urlencode($media->path)]);

        return [
            'thumbnail' => $baseUrl.'?w=150&h=150&fit=crop&q=85',
            'small' => $baseUrl.'?w=400&h=400&fit=contain&q=85',
            'medium' => $baseUrl.'?w=800&h=800&fit=contain&q=85',
            'large' => $baseUrl.'?w=1600&h=1600&fit=contain&q=90',
            'original' => $media->url,
            'custom' => $baseUrl, // Base URL for custom parameters
        ];
    }
}

