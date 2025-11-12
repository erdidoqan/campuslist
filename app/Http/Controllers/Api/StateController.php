<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\University;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StateController extends Controller
{
    /**
     * List all states (administrative areas) with university counts
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Get unique states with university counts
        $states = University::select('administrative_area', 'region_code')
            ->selectRaw('COUNT(*) as universities_count')
            ->whereNotNull('administrative_area')
            ->where('administrative_area', '!=', '')
            ->groupBy('administrative_area', 'region_code')
            ->orderBy('administrative_area', 'asc')
            ->get()
            ->map(function ($state) {
                return [
                    'administrative_area' => $state->administrative_area,
                    'region_code' => $state->region_code,
                    'universities_count' => $state->universities_count,
                ];
            });

        // Filter by region_code if provided
        if ($request->has('region_code')) {
            $regionCode = $request->get('region_code');
            $states = $states->filter(function ($state) use ($regionCode) {
                return $state['region_code'] === $regionCode;
            })->values();
        }

        // Filter by minimum universities count
        if ($request->has('min_universities')) {
            $minUniversities = (int) $request->get('min_universities');
            $states = $states->filter(function ($state) use ($minUniversities) {
                return $state['universities_count'] >= $minUniversities;
            })->values();
        }

        return response()->json([
            'success' => true,
            'data' => $states,
            'meta' => [
                'total' => $states->count(),
            ],
        ]);
    }

    /**
     * List universities in a specific state
     *
     * @param  Request  $request
     * @param  string  $administrativeArea
     * @return JsonResponse
     */
    public function show(Request $request, string $administrativeArea): JsonResponse
    {
        // Get universities in this state
        $query = University::query()
            ->with(['majorsRelation', 'notableMajorsRelation', 'score'])
            ->where('administrative_area', $administrativeArea);

        // Apply additional filters from request
        if ($request->has('locality')) {
            $query->where('locality', 'like', "%{$request->get('locality')}%");
        }

        if ($request->has('type')) {
            $query->where('type', $request->get('type'));
        }

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('short_name', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');

        $allowedSortFields = ['name', 'founded', 'acceptance_rate', 'enrollment_total', 'tuition_undergraduate'];

        if (in_array($sortBy, $allowedSortFields, true)) {
            $query->orderBy($sortBy, $sortOrder === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderBy('name', 'asc');
        }

        // Pagination
        $perPage = min((int) $request->get('per_page', 15), 100);
        $universities = $query->paginate($perPage);

        if ($universities->isEmpty() && $universities->currentPage() === 1) {
            return response()->json([
                'success' => false,
                'message' => 'Bu state için üniversite bulunamadı.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $universities->map(function ($university) {
                return $this->formatUniversity($university);
            }),
            'meta' => [
                'administrative_area' => $administrativeArea,
                'current_page' => $universities->currentPage(),
                'last_page' => $universities->lastPage(),
                'per_page' => $universities->perPage(),
                'total' => $universities->total(),
                'from' => $universities->firstItem(),
                'to' => $universities->lastItem(),
            ],
        ]);
    }

    /**
     * Format university data (simplified for state listing)
     *
     * @param  \App\Models\University  $university
     * @return array
     */
    protected function formatUniversity($university): array
    {
        return [
            'id' => $university->id,
            'name' => $university->name,
            'slug' => $university->slug,
            'short_name' => $university->short_name,
            'locality' => $university->locality,
            'website' => $university->website,
            'type' => $university->type,
            'overall_grade' => $university->score?->overall_grade,
            'acceptance_rate' => $university->acceptance_rate,
            'enrollment_total' => $university->enrollment_total,
            'tuition_undergraduate' => $university->tuition_undergraduate,
            'tuition_currency' => $university->tuition_currency,
            'founded_year' => $university->founded?->format('Y'),
        ];
    }

    /**
     * Get cities (localities) for a specific state
     *
     * @param  Request  $request
     * @param  string  $administrativeArea
     * @return JsonResponse
     */
    public function cities(Request $request, string $administrativeArea): JsonResponse
    {
        // Get unique cities in this state with university counts
        $cities = University::select('locality', 'administrative_area', 'region_code')
            ->selectRaw('COUNT(*) as universities_count')
            ->where('administrative_area', $administrativeArea)
            ->whereNotNull('locality')
            ->where('locality', '!=', '')
            ->groupBy('locality', 'administrative_area', 'region_code')
            ->orderBy('locality', 'asc')
            ->get()
            ->map(function ($city) {
                return [
                    'locality' => $city->locality,
                    'administrative_area' => $city->administrative_area,
                    'region_code' => $city->region_code,
                    'universities_count' => $city->universities_count,
                ];
            });

        if ($cities->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Bu state için şehir bulunamadı.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'administrative_area' => $administrativeArea,
                'region_code' => $cities->first()['region_code'] ?? null,
                'cities' => $cities,
                'total_cities' => $cities->count(),
            ],
        ]);
    }

    /**
     * Get all countries (region codes) with state counts
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function countries(Request $request): JsonResponse
    {
        // Get unique countries with counts
        $countries = University::select('region_code')
            ->selectRaw('COUNT(DISTINCT administrative_area) as states_count')
            ->selectRaw('COUNT(*) as universities_count')
            ->whereNotNull('region_code')
            ->where('region_code', '!=', '')
            ->groupBy('region_code')
            ->orderBy('region_code', 'asc')
            ->get()
            ->map(function ($country) {
                return [
                    'region_code' => $country->region_code,
                    'states_count' => $country->states_count,
                    'universities_count' => $country->universities_count,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $countries,
            'meta' => [
                'total' => $countries->count(),
            ],
        ]);
    }
}
