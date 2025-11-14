<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Major;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MajorController extends Controller
{
    /**
     * List majors with filtering and pagination
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Major::query()->withCount('universities');

        // Search by name
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('name', 'like', "%{$search}%");
        }

        // Filter by universities count (popular majors)
        if ($request->has('min_universities')) {
            $query->having('universities_count', '>=', (int) $request->get('min_universities'));
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');

        if ($sortBy === 'universities_count') {
            $query->orderBy('universities_count', $sortOrder === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderBy('name', $sortOrder === 'desc' ? 'desc' : 'asc');
        }

        // Pagination
        $perPage = min((int) $request->get('per_page', 50), 100);
        $majors = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $majors->map(function ($major) {
                return [
                    'id' => $major->id,
                    'name' => $major->name,
                    'slug' => $major->slug,
                    'title' => $major->title,
                    'meta_description' => $major->meta_description,
                    'universities_count' => $major->universities_count ?? 0,
                    'created_at' => $major->created_at?->toISOString(),
                    'updated_at' => $major->updated_at?->toISOString(),
                ];
            }),
            'meta' => [
                'current_page' => $majors->currentPage(),
                'last_page' => $majors->lastPage(),
                'per_page' => $majors->perPage(),
                'total' => $majors->total(),
                'from' => $majors->firstItem(),
                'to' => $majors->lastItem(),
            ],
        ]);
    }

    /**
     * Show major by slug
     *
     * @param  string  $slug
     * @return JsonResponse
     */
    public function showBySlug(string $slug): JsonResponse
    {
        $major = Major::withCount('universities')
            ->where('slug', $slug)
            ->first();

        if (! $major) {
            return response()->json([
                'success' => false,
                'message' => 'Major bulunamadı.',
            ], 404);
        }

        // Get universities offering this major with score and first media
        $perPage = min((int) request()->get('per_page', 20), 100);
        $universities = $major->universities()
            ->with(['score'])
            ->select(
                'universities.id',
                'universities.name',
                'universities.overview',
                'universities.acceptance_rate',
                'universities.tuition_undergraduate',
                'universities.tuition_currency',
                'universities.requirement_sat'
            )
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $major->id,
                'name' => $major->name,
                'slug' => $major->slug,
                'title' => $major->title,
                'meta_description' => $major->meta_description,
                'universities_count' => $major->universities_count ?? 0,
                'universities' => [
                    'data' => collect($universities->items())->map(function ($university) {
                        // Get first media for this university
                        $firstMedia = Media::forUniversity($university->id)
                            ->where('mime_type', 'like', 'image/%')
                            ->first();

                        $media = null;
                        if ($firstMedia) {
                            $baseUrl = route('glide', ['hashName' => $firstMedia->hash_name]);
                            $media = $baseUrl.'?w=200&h=200&fit=crop&q=85';
                        }

                        return [
                            'id' => $university->id,
                            'name' => $university->name,
                            'overview' => $university->overview,
                            'overall_grade' => $university->score?->overall_grade,
                            'acceptance_rate' => $university->acceptance_rate,
                            'tuition' => [
                                'undergraduate' => $university->tuition_undergraduate,
                                'currency' => $university->tuition_currency,
                            ],
                            'sat_score' => $university->requirement_sat,
                            'media' => $media,
                        ];
                    })->values(),
                    'meta' => [
                        'current_page' => $universities->currentPage(),
                        'last_page' => $universities->lastPage(),
                        'per_page' => $universities->perPage(),
                        'total' => $universities->total(),
                        'from' => $universities->firstItem(),
                        'to' => $universities->lastItem(),
                    ],
                ],
                'created_at' => $major->created_at?->toISOString(),
                'updated_at' => $major->updated_at?->toISOString(),
            ],
        ]);
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

        // Use hash_name for cleaner URLs (matches CDN filename)
        $baseUrl = route('glide', ['hashName' => $media->hash_name]);

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

