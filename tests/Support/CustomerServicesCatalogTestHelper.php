<?php

namespace Tests\Support;

use App\Models\Country;
use App\Models\Partner;
use App\Models\Product;
use App\Models\ProductServiceForm;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\PartnerPayableAccountService;
use Illuminate\Support\Str;

trait CustomerServicesCatalogTestHelper
{
    /**
     * @return array{
     *     country: Country,
     *     category: ServiceCategory,
     *     partner: Partner,
     *     service: Service,
     *     product: Product
     * }
     */
    protected function seedCustomerServicesCatalog(?Country $country = null): array
    {
        $country ??= Country::query()->create([
            'id' => (string) Str::uuid(),
            'name' => ['en' => 'Sudan', 'ar' => 'Sudan'],
            'short_name' => 'SD',
            'code' => '249',
            'status' => true,
        ]);

        $suffix = Str::lower(Str::random(8));

        $category = ServiceCategory::query()->create([
            'id' => (string) Str::uuid(),
            'type' => ServiceCategory::TYPE_SERVICE,
            'name_en' => 'Utilities',
            'name_ar' => 'Utilities',
            'code' => 'UTIL_'.$suffix,
            'is_active' => true,
        ]);

        $partner = Partner::query()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Test Partner',
            'email' => "partner-{$suffix}@example.com",
            'merchant_code' => 'MRC_'.$suffix,
            'country_id' => $country->id,
            'is_active' => true,
            'status' => 'approved',
        ]);

        $service = Service::query()->create([
            'id' => (string) Str::uuid(),
            'category_id' => $category->id,
            'partner_id' => $partner->id,
            'country_id' => $country->id,
            'service_type' => 'digital',
            'service_name' => ['en' => 'Electricity Bill', 'ar' => 'Electricity Bill'],
            'description' => ['en' => 'Pay electricity bills', 'ar' => 'Pay electricity bills'],
            'status' => 'active',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'id' => (string) Str::uuid(),
            'service_id' => $service->id,
            'country_id' => $country->id,
            'name' => ['en' => 'Standard Plan', 'ar' => 'Standard Plan'],
            'description' => ['en' => 'Default product', 'ar' => 'Default product'],
            'status' => true,
        ]);

        return compact('country', 'category', 'partner', 'service', 'product');
    }

    /**
     * @return array{
     *     country: Country,
     *     category: ServiceCategory,
     *     partner: Partner,
     *     service: Service,
     *     product: Product,
     *     form: ProductServiceForm,
     *     payableAccountCode: int
     * }
     */
    protected function seedPartnerWithPayableAccount(
        ?Country $country = null,
        ?string $formUrl = 'https://bill-mock.test/pay'
    ): array {
        $catalog = $this->seedCustomerServicesCatalog($country);
        $partner = $catalog['partner'];

        $payable = app(PartnerPayableAccountService::class)->allocateForPartner($partner->fresh());
        $partner->refresh();

        $form = ProductServiceForm::query()->create([
            'product_id' => $catalog['product']->id,
            'form_name' => ['en' => 'Bill Form', 'ar' => 'Bill Form'],
            'form_url' => $formUrl,
            'country_id' => $catalog['country']->id,
        ]);

        return [
            ...$catalog,
            'form' => $form,
            'payableAccountCode' => (int) $payable->code,
        ];
    }
}
