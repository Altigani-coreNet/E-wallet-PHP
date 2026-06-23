<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MerchantServiceCatalogResource;
use App\Models\Service;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MerchantServiceController extends Controller
{
    use ApiResponse;

    /**
     * Build base merchant catalog query with required relationships.
     */
    private function catalogQuery(Request $request, bool $enforceAuthenticatedPartnerScope = true)
    {
        $query = Service::query()
            ->where('is_active', true)
            ->where('status', 'active')
            ->whereHas('products', function ($productQuery) {
                $productQuery->where('status', true);
            })
            ->with([
                'country',
                'products' => function ($productQuery) {
                    $productQuery->where('status', true)
                        ->with([
                            'country',
                            'serviceForms' => function ($formQuery) {
                                $formQuery->with('fields');
                            },
                        ])->orderBy('id');
                },
            ])
            ->orderBy('id');

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(service_name, '$.en')) like ?", ["%{$search}%"])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(service_name, '$.ar')) like ?", ["%{$search}%"])
                    ->orWhere('short_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('country_id')) {
            $query->where('country_id', $request->input('country_id'));
        }

        if ($request->filled('service_type')) {
            $query->where('service_type', $request->input('service_type'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $user = $enforceAuthenticatedPartnerScope ? Auth::user() : null;
        $authenticatedPartnerId = $user?->partner_id ?? $user?->content_provider_id;

        if ($authenticatedPartnerId) {
            $query->where('partner_id', $authenticatedPartnerId);
        } elseif ($request->filled('partner_id')) {
            $query->where('partner_id', $request->input('partner_id'));
        } elseif ($request->filled('merchant_id')) {
            // Fallback filter for non partner-scoped users.
            $query->where('partner_id', $request->input('merchant_id'));
        }

        return $query;
    }

    /**
     * Return full merchant service catalog in one response.
     * services -> products -> product_forms -> product_form_fields
     */
    public function catalog(Request $request): JsonResponse
    {
        try {
            $services = $this->catalogQuery($request, true)->get();

            return $this->SuccessMessage([
                'services' => MerchantServiceCatalogResource::collection($services),
            ]);
        } catch (\Throwable $e) {
            return $this->ErrorMessage(
                'Failed to fetch merchant services catalog: ' . $e->getMessage(),
                'MERCHANT_CATALOG_FETCH_ERROR',
                500
            );
        }
    }

    /**
     * Home-screen merchant services catalog.
     * Same structure as catalog(), but limited (default 5).
     */
    public function homeServices(Request $request): JsonResponse
    {
        try {
            $limit = (int) $request->input('limit', 5);
            $limit = max(1, min($limit, 50));

            $services = $this->catalogQuery($request, true)
                ->limit($limit)
                ->get();

            return $this->SuccessMessage([
                'services' => MerchantServiceCatalogResource::collection($services),
            ]);
        } catch (\Throwable $e) {
            return $this->ErrorMessage(
                'Failed to fetch home services catalog: ' . $e->getMessage(),
                'MERCHANT_HOME_CATALOG_FETCH_ERROR',
                500
            );
        }
    }

    /**
     * Return full service catalog for admin preview.
     * Same shape as merchant catalog, without forced partner scope.
     */
    public function catalogForAdmin(Request $request): JsonResponse
    {
        try {
            $services = $this->catalogQuery($request, false)->get();

            return $this->SuccessMessage([
                'services' => MerchantServiceCatalogResource::collection($services),
            ]);
        } catch (\Throwable $e) {
            return $this->ErrorMessage(
                'Failed to fetch admin services catalog: ' . $e->getMessage(),
                'ADMIN_CATALOG_FETCH_ERROR',
                500
            );
        }
    }
}

