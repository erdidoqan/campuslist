<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Major;
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

        // Get universities offering this major
        $perPage = min((int) request()->get('per_page', 20), 100);
        $universities = $major->universities()
            ->select('universities.id', 'universities.name', 'universities.slug', 'universities.location')
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
                    'data' => $universities->items(),
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
}

