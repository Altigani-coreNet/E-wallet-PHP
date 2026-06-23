<?php

namespace App\Http\Controllers\Api\V2\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminHomeScreenSearchItemResource;
use App\Models\HomeScreenConfiguration;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Traits\ApiResponse;
use App\Traits\BroadcastsServicesListingUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminHomeScreenConfigurationController extends Controller
{
    use ApiResponse;
    use BroadcastsServicesListingUpdate;

    public function index(): JsonResponse
    {
        $items = HomeScreenConfiguration::query()
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get();

        return $this->SuccessMessage([
            'items' => $this->hydrateConfigItems($items),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $limit = (int) ($validated['limit'] ?? 5);

        if ($search === '') {
            return $this->SuccessMessage(['items' => []]);
        }

        // Normalize search input for predictable matching regardless of user casing/spacing.
        $normalizedSearch = mb_strtolower(preg_replace('/\s+/', ' ', $search));
        $like = "%{$normalizedSearch}%";

        $categoriesQuery = DB::table('service_categories as sc')
            ->selectRaw("
                'category' as type,
                sc.id as id,
                COALESCE(sc.name_en, sc.name_ar, sc.code, sc.id) as label,
                COALESCE(sc.name_ar, sc.code) as subtitle,
                sc.image as image,
                sc.is_active as is_active,
                sc.updated_at as sort_at
            ")
            ->whereNull('sc.deleted_at')
            ->where('sc.type', ServiceCategory::TYPE_SERVICE)
            ->where(function ($query) use ($like) {
                $query->whereRaw('LOWER(sc.id) like ?', [$like])
                    ->orWhereRaw('LOWER(sc.name_en) like ?', [$like])
                    ->orWhereRaw('LOWER(sc.name_ar) like ?', [$like])
                    ->orWhereRaw('LOWER(sc.code) like ?', [$like]);
            });

        $servicesQuery = DB::table('services as s')
            ->selectRaw("
                'service' as type,
                s.id as id,
                COALESCE(
                    JSON_UNQUOTE(JSON_EXTRACT(s.service_name, '$.en')),
                    JSON_UNQUOTE(JSON_EXTRACT(s.service_name, '$.ar')),
                    s.id
                ) as label,
                JSON_UNQUOTE(JSON_EXTRACT(s.service_name, '$.ar')) as subtitle,
                s.image as image,
                s.is_active as is_active,
                s.updated_at as sort_at
            ")
            ->whereNull('s.deleted_at')
            ->where(function ($query) use ($like) {
                $query->whereRaw('LOWER(s.id) like ?', [$like])
                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(s.service_name, '$.en'))) like ?", [$like])
                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(s.service_name, '$.ar'))) like ?", [$like]);
            });

        $productsQuery = DB::table('products as p')
            ->selectRaw("
                'product' as type,
                p.id as id,
                COALESCE(
                    JSON_UNQUOTE(JSON_EXTRACT(p.name, '$.en')),
                    JSON_UNQUOTE(JSON_EXTRACT(p.name, '$.ar')),
                    p.id
                ) as label,
                JSON_UNQUOTE(JSON_EXTRACT(p.name, '$.ar')) as subtitle,
                p.image as image,
                p.status as is_active,
                p.updated_at as sort_at
            ")
            ->whereNull('p.deleted_at')
            ->where(function ($query) use ($like) {
                $query->whereRaw('LOWER(p.id) like ?', [$like])
                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(p.name, '$.en'))) like ?", [$like])
                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(p.name, '$.ar'))) like ?", [$like]);
            });

        $unifiedQuery = $categoriesQuery
            ->unionAll($servicesQuery)
            ->unionAll($productsQuery);

        $items = DB::query()
            ->fromSub($unifiedQuery, 'search_items')
            ->orderByRaw("
                CASE type
                    WHEN 'category' THEN 1
                    WHEN 'service' THEN 2
                    ELSE 3
                END
            ")
            ->orderBy('label')
            ->limit($limit)
            ->get();

        return $this->SuccessMessage([
            'items' => AdminHomeScreenSearchItemResource::collection($items),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            // Allow empty array: [] means clear all saved home-screen items.
            'items' => ['present', 'array'],
            'items.*.id' => ['required', 'uuid'],
            'items.*.type' => ['required', Rule::in(['category', 'service', 'product'])],
            'items.*.is_quick_action' => ['nullable', 'boolean'],
        ]);

        $items = collect($validated['items'])
            ->map(fn (array $item) => [
                'id' => $item['id'],
                'type' => $item['type'],
                'is_quick_action' => (bool) ($item['is_quick_action'] ?? false),
            ])
            ->unique(fn (array $item) => "{$item['type']}:{$item['id']}")
            ->values();

        DB::transaction(function () use ($items) {
            HomeScreenConfiguration::query()->delete();

            $now = now();
            $rows = $items->values()->map(function (array $item, int $index) use ($now) {
                return [
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'configurable_type' => $this->mapTypeToMorphClass($item['type']),
                    'configurable_id' => $item['id'],
                    'sort_order' => $index + 1,
                    'is_quick_action' => (bool) ($item['is_quick_action'] ?? false),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->all();

            if (! empty($rows)) {
                HomeScreenConfiguration::query()->insert($rows);
            }
        });

        // Notify subscribers (e.g. merchant catalog, dashboards) that the home screen config changed.
        $this->broadcastServicesListingUpdate();

        return $this->SuccessMessage($this->appendServicesListingBroadcastWarning([
            'items' => $this->hydrateConfigItems(
                HomeScreenConfiguration::query()->orderBy('sort_order')->get()
            ),
        ]));
    }

    private function mapTypeToMorphClass(string $type): string
    {
        return match ($type) {
            'category' => ServiceCategory::class,
            'service' => Service::class,
            'product' => Product::class,
        };
    }

    private function mapMorphClassToType(string $className): ?string
    {
        return match ($className) {
            ServiceCategory::class => 'category',
            Service::class => 'service',
            Product::class => 'product',
            default => null,
        };
    }

    /**
     * @param Collection<int, HomeScreenConfiguration> $items
     * @return array<int, array<string, mixed>>
     */
    private function hydrateConfigItems(Collection $items): array
    {
        $items->loadMorph('configurable', [
            ServiceCategory::class => [],
            Service::class => [],
            Product::class => [],
        ]);

        return $items->map(function (HomeScreenConfiguration $item) {
            $type = $this->mapMorphClassToType($item->configurable_type);
            if (! $type) {
                return null;
            }

            $entity = $item->configurable;
            if (! $entity) {
                return null;
            }

            $label = $item->configurable_id;
            $subtitle = null;
            $image = null;
            $isActive = true;

            if ($type === 'category') {
                $label = $entity->name_en ?: ($entity->name_ar ?: $entity->code);
                $subtitle = $entity->name_ar ?: $entity->code;
                $image = $entity->image;
                $isActive = (bool) $entity->is_active;
            } elseif ($type === 'service') {
                $label = $entity->serviceNameForLocale('en') ?: ($entity->serviceNameForLocale('ar') ?: $entity->id);
                $subtitle = $entity->serviceNameForLocale('ar') ?: null;
                $image = $entity->image;
                $isActive = (bool) $entity->is_active;
            } elseif ($type === 'product') {
                $name = is_array($entity->name) ? $entity->name : (array) $entity->name;
                $label = $name['en'] ?? ($name['ar'] ?? null);
                if (! $label) {
                    // Fall back to the first non-empty translation key before using raw id.
                    $firstNonEmpty = collect($name)->first(function ($value) {
                        return is_string($value) && trim($value) !== '';
                    });
                    $label = $firstNonEmpty ?: $entity->id;
                }
                $subtitle = $name['ar'] ?? ($name['en'] ?? null);
                $image = $entity->image;
                $isActive = (bool) $entity->status;
            }

            return [
                'id' => $item->configurable_id,
                'type' => $type,
                'label' => $label,
                'subtitle' => $subtitle,
                'image' => $image ? coreservice_asset($image) : null,
                'is_active' => $isActive,
                'sort_order' => $item->sort_order,
                'is_quick_action' => (bool) $item->is_quick_action,
            ];
        })->filter()->values()->all();
    }
}
