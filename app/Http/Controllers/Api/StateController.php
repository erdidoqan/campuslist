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
     * Show state details
     *
     * @param  Request  $request
     * @param  string  $administrativeArea
     * @return JsonResponse
     */
    public function show(Request $request, string $administrativeArea): JsonResponse
    {
        // Get state info
        $stateInfo = University::select('administrative_area', 'region_code')
            ->selectRaw('COUNT(*) as universities_count')
            ->selectRaw('COUNT(DISTINCT locality) as cities_count')
            ->where('administrative_area', $administrativeArea)
            ->groupBy('administrative_area', 'region_code')
            ->first();

        if (! $stateInfo) {
            return response()->json([
                'success' => false,
                'message' => 'State bulunamadı.',
            ], 404);
        }

        // Get top cities in this state
        $topCities = University::select('locality')
            ->selectRaw('COUNT(*) as universities_count')
            ->where('administrative_area', $administrativeArea)
            ->whereNotNull('locality')
            ->where('locality', '!=', '')
            ->groupBy('locality')
            ->orderByDesc('universities_count')
            ->limit(10)
            ->get()
            ->map(function ($city) use ($administrativeArea, $stateInfo) {
                return [
                    'locality' => $city->locality,
                    'universities_count' => $city->universities_count,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'administrative_area' => $stateInfo->administrative_area,
                'region_code' => $stateInfo->region_code,
                'universities_count' => $stateInfo->universities_count,
                'cities_count' => $stateInfo->cities_count,
                'top_cities' => $topCities,
            ],
        ]);
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
