<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

class CityController extends Controller
{
    use ApiResponse;

    /**
     * City `name` JSON keys used for search (must match seeded translation keys).
     */
    private const NAME_SEARCH_LOCALES = ['en', 'ar'];

    private function sanitizeLocale(?string $locale, string $default = 'en'): string
    {
        if ($locale === null || $locale === '') {
            return $default;
        }

        $primary = strtolower(explode('-', str_replace('_', '-', $locale))[0] ?? '');

        return preg_match('/^[a-z]{2}$/', $primary) ? $primary : $default;
    }

    /**
     * @OA\Get(
     *     path="/api/cities",
     *     summary="Get cities",
     *     description="Retrieves a list of cities, optionally filtered by country",
     *     tags={"Company Registration"},
     *     @OA\Parameter(
     *         name="country_id",
     *         in="query",
     *         description="Filter cities by country ID",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cities retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Cities retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/CityResource")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to fetch cities",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to fetch cities: Error message")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            $query = City::where('status', 1)
                ->with('country:id,name,short_name,code')
                ->select('id', 'name', 'country_id');

            if ($request->has('country_id') && $request->country_id) {
                $query->where('country_id', $request->country_id);
            }

            $cities = $query->orderBy('name->en')->get();

            return $this->SuccessMessage($cities);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch cities: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/cities/select",
     *     summary="Get cities for select dropdown",
     *     description="Retrieves cities formatted for select dropdown, optionally filtered by country",
     *     tags={"Company Registration"},
     *     @OA\Parameter(
     *         name="country_id",
     *         in="query",
     *         description="Filter cities by country ID",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cities for select retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Cities for select retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/CitySelectResource")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to fetch cities for select",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to fetch cities for select: Error message")
     *         )
     *     )
     * )
     */
    public function select(Request $request)
    {
        try {
            $lang = $this->sanitizeLocale($request->get('lang'));

            $query = City::where('status', 1)
                ->select('id', 'name', 'country_id');

            if ($request->has('country_id') && $request->country_id) {
                $query->where('country_id', $request->country_id);
            }

            if ($request->filled('search')) {
                $rawSearch = trim((string) $request->search);
                $searchTerm = function_exists('mb_strtolower')
                    ? mb_strtolower($rawSearch, 'UTF-8')
                    : strtolower($rawSearch);
                $likePattern = '%' . addcslashes($searchTerm, '%_\\') . '%';

                $locales = array_values(array_unique(array_merge([$lang], self::NAME_SEARCH_LOCALES)));

                $query->where(function ($q) use ($likePattern, $locales) {
                    foreach ($locales as $locale) {
                        $q->orWhereRaw(
                            'LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, ?))) LIKE ?',
                            ['$.' . $locale, $likePattern]
                        );
                    }
                });
            }

            $cities = $query->orderByRaw("name->'$." . $lang . "'")
                ->get()
                ->map(function ($city) use ($lang) {
                    return [
                        'id' => $city->id,
                        'text' => $city->name,
                        'name' => $city->name,
                        'country_id' => $city->country_id,
                    ];
                });

            return $this->SuccessMessage($cities);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch cities for select: ' . $e->getMessage(), null, 500);
        }
    }
}
