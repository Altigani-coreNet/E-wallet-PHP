<?php

namespace Database\Seeders;

use App\Enums\BusinessType;
use App\Models\Country;
use App\Models\Partner;
use App\Models\Product;
use App\Models\ProductServiceForm;
use App\Models\ProductServiceFormField;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceSubCategory;
use App\Models\ServiceType;
use Illuminate\Database\Seeder;

class TelecomTopupFlowSeeder extends Seeder
{
    public function run(): void
    {
        $sudanCountry = Country::query()
            ->where('short_name', 'SD')
            ->orWhere('code', '249')
            ->first();

        if (! $sudanCountry) {
            throw new \RuntimeException(
                'Sudan not found in countries. Seed currencies, then CountrySeeder, before TelecomTopupFlowSeeder.'
            );
        }

        $countryId = $sudanCountry->id;

        $partnerTelecomCategory = ServiceCategory::query()->updateOrCreate(
            [
                'type' => ServiceCategory::TYPE_PARTNER,
                'code' => 'PCAT_TELECOM',
            ],
            [
                'type' => ServiceCategory::TYPE_PARTNER,
                'name_en' => 'Telecom & top-up partner',
                'name_ar' => 'شريك اتصالات وشحن',
                'description' => 'Mobile operators and airtime partners',
                'is_active' => true,
            ]
        );

        $partnerPaymentProviderCategory = ServiceCategory::query()->updateOrCreate(
            [
                'type' => ServiceCategory::TYPE_PARTNER,
                'code' => 'PCAT_PAYMENT_PROVIDER',
            ],
            [
                'type' => ServiceCategory::TYPE_PARTNER,
                'name_en' => 'Payment provider partner',
                'name_ar' => 'شريك مزود دفع',
                'description' => 'POS and payment acceptance partners',
                'is_active' => true,
            ]
        );

        $category = ServiceCategory::query()->updateOrCreate(
            [
                'type' => ServiceCategory::TYPE_SERVICE,
                'code' => 'CAT_TELECOM',
            ],
            [
                'type' => ServiceCategory::TYPE_SERVICE,
                'name_en' => 'Telecom',
                'name_ar' => 'الاتصالات',
                'description' => 'Telecom services',
                'is_active' => true,
            ]
        );

        $subCategory = ServiceSubCategory::query()->updateOrCreate(
            ['code' => 'SCAT_TOPUP'],
            [
                'category_id' => $category->id,
                'name_en' => 'Topup',
                'name_ar' => 'شحن الرصيد',
                'description' => 'Topup services',
                'is_active' => true,
            ]
        );

        $prepaidType = ServiceType::query()->updateOrCreate(
            ['code' => 'TOPUP_PREPAID'],
            [
                'name_en' => 'Prepaid',
                'name_ar' => 'مسبق الدفع',
                'description' => 'Prepaid topup product',
                'is_active' => true,
            ]
        );

        $postpaidType = ServiceType::query()->updateOrCreate(
            ['code' => 'TOPUP_POSTPAID'],
            [
                'name_en' => 'Postpaid',
                'name_ar' => 'آجل الدفع',
                'description' => 'Postpaid topup product',
                'is_active' => true,
            ]
        );

        $services = [
            [
                'name_en' => 'Sudani Topup',
                'name_ar' => 'شحن سوداني',
                'partner_email' => 'sudani.topup.partner@sudani.com',
                'partner_name' => 'Sudani Telecom Partner',
                'image' => 'Sudani-Logo.png',
            ],
            [
                'name_en' => 'Zain Topup',
                'name_ar' => 'شحن زين',
                'partner_email' => 'zain.topup.partner@example.com',
                'partner_name' => 'Zain Telecom Partner',
                'image' => 'zain-logo.jpg',
            ],
        ];

        foreach ($services as $serviceSeed) {
            $partner = Partner::query()->updateOrCreate(
                ['email' => $serviceSeed['partner_email']],
                [
                    'name' => $serviceSeed['partner_name'],
                    'owner_name' => $serviceSeed['partner_name'],
                    'phone' => '+249000000000',
                    'address' => 'Khartoum, Sudan',
                    'business_type' => BusinessType::SERVICES->value,
                    'country_id' => $countryId,
                    'partner_category_id' => $partnerTelecomCategory->id,
                    'status' => 'approved',
                    'is_active' => true,
                    'logo' => $serviceSeed['image'],
                ]
            );

            $service = Service::query()->updateOrCreate(
                [
                    'partner_id' => $partner->id,
                    'category_id' => $category->id,
                    'sub_category_id' => $subCategory->id,
                ],
                [
                    'country_id' => $countryId,
                    'service_type' => 'digital',
                    'image' => $serviceSeed['image'],
                    'service_name' => [
                        'en' => $serviceSeed['name_en'],
                        'ar' => $serviceSeed['name_ar'],
                    ],
                    'description' => [
                        'en' => $serviceSeed['name_en'] . ' service',
                        'ar' => 'خدمة ' . $serviceSeed['name_ar'],
                    ],
                    'status' => 'active',
                    'is_active' => true,
                ]
            );

            $products = [
                [
                    'name_en' => 'Prepaid',
                    'name_ar' => 'مسبق الدفع',
                    'type_id' => $prepaidType->id,
                ],
                [
                    'name_en' => 'Postpaid',
                    'name_ar' => 'آجل الدفع',
                    'type_id' => $postpaidType->id,
                ],
            ];

            foreach ($products as $productSeed) {
                $product = Product::query()->updateOrCreate(
                    [
                        'service_id' => $service->id,
                        'type_id' => $productSeed['type_id'],
                    ],
                    [
                        'country_id' => $countryId,
                        'service_sub_category_id' => $subCategory->id,
                        'image' => $serviceSeed['image'],
                        'name' => [
                            'en' => $productSeed['name_en'],
                            'ar' => $productSeed['name_ar'],
                        ],
                        'status' => true,
                    ]
                );

                $form = ProductServiceForm::query()->updateOrCreate(
                    ['product_id' => $product->id],
                    [
                        'form_name' => [
                            'en' => 'Mobile Services Form',
                            'ar' => 'نموذج خدمات الهاتف',
                        ],
                        'form_url' => '',
                        'country_id' => $countryId,
                    ]
                );

                ProductServiceFormField::query()->updateOrCreate(
                    ['product_service_form_id' => $form->id, 'key' => 'phone_number'],
                    [
                        'label_en' => 'Phone Number',
                        'label_ar' => 'رقم الهاتف',
                        'type' => 'Text Field',
                        'options_json' => null,
                        'sort_order' => 0,
                        'is_required' => true,
                        'status' => true,
                        'country_id' => $countryId,
                    ]
                );
            }
        }

        $salesCategory = ServiceCategory::query()->updateOrCreate(
            [
                'type' => ServiceCategory::TYPE_SERVICE,
                'code' => 'CAT_SALES',
            ],
            [
                'type' => ServiceCategory::TYPE_SERVICE,
                'name_en' => 'Sales',
                'name_ar' => 'المبيعات',
                'description' => 'Sales services',
                'is_active' => true,
            ]
        );

        $paymentProviderSubCategory = ServiceSubCategory::query()->updateOrCreate(
            ['code' => 'SCAT_PAYMENT_PROVIDER'],
            [
                'category_id' => $salesCategory->id,
                'name_en' => 'Payment Provider',
                'name_ar' => 'مزود الدفع',
                'description' => 'Payment provider services',
                'is_active' => true,
            ]
        );

        $tapToPayType = ServiceType::query()->updateOrCreate(
            ['code' => 'SALES_TAP_TO_PAY'],
            [
                'name_en' => 'Tap to Pay',
                'name_ar' => 'الدفع باللمس',
                'description' => 'Tap to pay product',
                'is_active' => true,
            ]
        );

        $fastPosPartner = Partner::query()->updateOrCreate(
            ['email' => 'fastpos.partner@fastpay.com'],
            [
                'name' => 'FastPos Payment Provider',
                'owner_name' => 'FastPos Payment Provider',
                'phone' => '+249000000001',
                'address' => 'Khartoum, Sudan',
                'business_type' => BusinessType::SERVICES->value,
                'country_id' => $countryId,
                'partner_category_id' => $partnerPaymentProviderCategory->id,
                'status' => 'approved',
                'is_active' => true,
                'logo' => 'faspay_logo_1.png',
            ]
        );

        $fastPosService = Service::query()->updateOrCreate(
            [
                'partner_id' => $fastPosPartner->id,
                'category_id' => $salesCategory->id,
                'sub_category_id' => $paymentProviderSubCategory->id,
            ],
            [
                'country_id' => $countryId,
                'service_type' => 'digital',
                'image' => 'faspay_logo_1.png',
                'service_name' => [
                    'en' => 'Tap to Pay',
                    'ar' => 'الدفع باللمس',
                ],
                'description' => [
                    'en' => 'FastPos tap to pay service',
                    'ar' => 'خدمة الدفع باللمس من فاست بوس',
                ],
                'status' => 'active',
                'is_active' => true,
            ]
        );

        $fastPosProduct = Product::query()->updateOrCreate(
            [
                'service_id' => $fastPosService->id,
                'type_id' => $tapToPayType->id,
            ],
            [
                'country_id' => $countryId,
                'service_sub_category_id' => $paymentProviderSubCategory->id,
                'image' => 'faspay_logo_1.png',
                'name' => [
                    'en' => 'Tap to Pay',
                    'ar' => 'الدفع باللمس',
                ],
                'status' => true,
            ]
        );

        $fastPosForm = ProductServiceForm::query()->updateOrCreate(
            ['product_id' => $fastPosProduct->id],
            [
                'form_name' => [
                    'en' => 'FastPos Payment Form',
                    'ar' => 'نموذج دفع فاست بوس',
                ],
                'form_url' => '',
                'country_id' => $countryId,
            ]
        );

        ProductServiceFormField::query()->updateOrCreate(
            ['product_service_form_id' => $fastPosForm->id, 'key' => 'amount'],
            [
                'label_en' => 'Amount',
                'label_ar' => 'المبلغ',
                'type' => 'Text Field',
                'options_json' => null,
                'sort_order' => 0,
                'is_required' => true,
                'status' => true,
                'country_id' => $countryId,
            ]
        );
    }
}

