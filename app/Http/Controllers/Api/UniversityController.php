<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\University;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UniversityController extends Controller
{
    /**
     * List universities with filtering and pagination
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = University::query()->with(['majorsRelation', 'notableMajorsRelation']);

        // Search by name
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('short_name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        // Filter by location
        if ($request->has('location')) {
            $query->where('location', 'like', "%{$request->get('location')}%");
        }

        if ($request->has('region_code')) {
            $query->where('region_code', $request->get('region_code'));
        }

        if ($request->has('administrative_area')) {
            $query->where('administrative_area', 'like', "%{$request->get('administrative_area')}%");
        }

        if ($request->has('locality')) {
            $query->where('locality', 'like', "%{$request->get('locality')}%");
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->get('type'));
        }

        // Filter by acceptance rate
        if ($request->has('acceptance_rate_min')) {
            $query->where('acceptance_rate', '>=', (int) $request->get('acceptance_rate_min'));
        }

        if ($request->has('acceptance_rate_max')) {
            $query->where('acceptance_rate', '<=', (int) $request->get('acceptance_rate_max'));
        }

        // Filter by enrollment
        if ($request->has('enrollment_min')) {
            $query->where('enrollment_total', '>=', (int) $request->get('enrollment_min'));
        }

        if ($request->has('enrollment_max')) {
            $query->where('enrollment_total', '<=', (int) $request->get('enrollment_max'));
        }

        if ($request->has('enrollment_undergraduate_min')) {
            $query->where('enrollment_undergraduate', '>=', (int) $request->get('enrollment_undergraduate_min'));
        }

        if ($request->has('enrollment_graduate_min')) {
            $query->where('enrollment_graduate', '>=', (int) $request->get('enrollment_graduate_min'));
        }

        // Filter by tuition
        if ($request->has('tuition_min')) {
            $query->where('tuition_undergraduate', '>=', (int) $request->get('tuition_min'));
        }

        if ($request->has('tuition_max')) {
            $query->where('tuition_undergraduate', '<=', (int) $request->get('tuition_max'));
        }

        if ($request->has('tuition_currency')) {
            $query->where('tuition_currency', $request->get('tuition_currency'));
        }

        // Filter by requirements
        if ($request->has('gpa_min')) {
            $query->where('requirement_gpa_min', '<=', (float) $request->get('gpa_min'));
        }

        if ($request->has('sat_max')) {
            $query->where('requirement_sat', '<=', (int) $request->get('sat_max'));
        }

        if ($request->has('act_max')) {
            $query->where('requirement_act', '<=', (int) $request->get('act_max'));
        }

        // Filter by majors (many-to-many)
        if ($request->has('majors')) {
            $majorIds = is_array($request->get('majors')) 
                ? $request->get('majors') 
                : explode(',', $request->get('majors'));
            
            $majorIds = array_filter(array_map('intval', $majorIds));
            
            if (!empty($majorIds)) {
                $query->whereHas('majorsRelation', function ($q) use ($majorIds) {
                    $q->whereIn('majors.id', $majorIds);
                });
            }
        }

        // Filter by notable majors only
        if ($request->has('notable_majors')) {
            $majorIds = is_array($request->get('notable_majors')) 
                ? $request->get('notable_majors') 
                : explode(',', $request->get('notable_majors'));
            
            $majorIds = array_filter(array_map('intval', $majorIds));
            
            if (!empty($majorIds)) {
                $query->whereHas('notableMajorsRelation', function ($q) use ($majorIds) {
                    $q->whereIn('majors.id', $majorIds);
                });
            }
        }

        // Filter by founded year
        if ($request->has('founded_min')) {
            $query->whereYear('founded', '>=', (int) $request->get('founded_min'));
        }

        if ($request->has('founded_max')) {
            $query->whereYear('founded', '<=', (int) $request->get('founded_max'));
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');

        $allowedSortFields = [
            'name', 'founded', 'acceptance_rate', 
            'enrollment_total', 'tuition_undergraduate', 
            'requirement_gpa_min', 'requirement_sat', 'requirement_act'
        ];

        if (in_array($sortBy, $allowedSortFields, true)) {
            $query->orderBy($sortBy, $sortOrder === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderBy('name', 'asc');
        }

        // Pagination
        $perPage = min((int) $request->get('per_page', 15), 100); // Max 100 per page
        $universities = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $universities->items(),
            'meta' => [
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
     * Show university by ID
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $university = University::with(['majorsRelation', 'notableMajorsRelation'])->find($id);

        if (! $university) {
            return response()->json([
                'success' => false,
                'message' => 'Üniversite bulunamadı.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatUniversity($university),
        ]);
    }

    /**
     * Show university by slug
     *
     * @param  string  $slug
     * @return JsonResponse
     */
    public function showBySlug(string $slug): JsonResponse
    {
        $university = University::with(['majorsRelation', 'notableMajorsRelation'])
            ->where('slug', $slug)
            ->first();

        if (! $university) {
            return response()->json([
                'success' => false,
                'message' => 'Üniversite bulunamadı.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatUniversity($university),
        ]);
    }

    /**
     * Format university data for API response
     *
     * @param  University  $university
     * @return array<string, mixed>
     */
    protected function formatUniversity(University $university): array
    {
        return [
            'id' => $university->id,
            'name' => $university->name,
            'slug' => $university->slug,
            'short_name' => $university->short_name,
            'location' => $university->location,
            'region_code' => $university->region_code,
            'administrative_area' => $university->administrative_area,
            'locality' => $university->locality,
            'website' => $university->website,
            'founded' => $university->founded?->format('Y-m-d'),
            'founded_year' => $university->founded?->format('Y'),
            'type' => $university->type,
            'meta_description' => $university->meta_description,
            'overview' => $university->overview,
            'google_maps_uri' => $university->google_maps_uri,
            'address' => $university->address,
            'phone' => $university->phone,
            'gps_coordinates' => $university->gps_coordinates,
            'acceptance_rate' => $university->acceptance_rate,
            'ranking' => $university->ranking,
            'enrollment' => [
                'total' => $university->enrollment_total,
                'undergraduate' => $university->enrollment_undergraduate,
                'graduate' => $university->enrollment_graduate,
                'raw' => $university->enrollment, // JSON backup
            ],
            'tuition' => [
                'undergraduate' => $university->tuition_undergraduate,
                'graduate' => $university->tuition_graduate,
                'international' => $university->tuition_international,
                'currency' => $university->tuition_currency,
                'raw' => $university->tuition, // JSON backup
            ],
            'requirements' => [
                'gpa_min' => $university->requirement_gpa_min,
                'sat' => $university->requirement_sat,
                'act' => $university->requirement_act,
                'toefl' => $university->requirement_toefl,
                'ielts' => $university->requirement_ielts,
                'raw' => $university->requirements, // JSON backup
            ],
            'deadlines' => $university->deadlines,
            'majors' => $university->majorsRelation->map(function ($major) {
                return [
                    'id' => $major->id,
                    'name' => $major->name,
                    'slug' => $major->slug,
                    'is_notable' => $major->pivot->is_notable ?? false,
                ];
            }),
            'notable_majors' => $university->notableMajorsRelation->map(function ($major) {
                return [
                    'id' => $major->id,
                    'name' => $major->name,
                    'slug' => $major->slug,
                ];
            }),
            'majors_raw' => $university->majors, // JSON backup
            'notable_majors_raw' => $university->notable_majors, // JSON backup
            'scholarships' => $university->scholarships,
            'housing' => $university->housing,
            'campus_life' => $university->campus_life,
            'contact' => $university->contact,
            'faq' => $university->faq,
            'created_at' => $university->created_at?->toISOString(),
            'updated_at' => $university->updated_at?->toISOString(),
        ];
    }
}

