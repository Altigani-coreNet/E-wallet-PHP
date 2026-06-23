<?php


namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeatureSeeder extends Seeder
{
    public function run()
    {
        // Plan 1 Features (Essential)
        $plan1_features = [
            [
                'name' => json_encode(['en' => 'Business Dashboard and Verification', 'ar' => 'لوحة تحكم الأعمال والتحقق']),
                'plan_id' => 1, // Plan 1
                'is_enabled' => true,
            ],
            [
                'name' => json_encode(['en' => 'Unlimited Project Listings', 'ar' => 'إدراج مشاريع غير محدود']),
                'plan_id' => 1,
                'is_enabled' => true,
            ],
            [
                'name' => json_encode(['en' => 'Lead Transfer from Profile', 'ar' => 'نقل العملاء المحتملين من الملف الشخصي']),
                'plan_id' => 1,
                'is_enabled' => true,
            ],
            [
                'name' => json_encode(['en' => 'Traffic Analysis', 'ar' => 'تحليل حركة المرور']),
                'plan_id' => 1,
                'is_enabled' => true,
            ],
            [
                'name' => json_encode(['en' => 'Market and Community Support', 'ar' => 'دعم السوق والمجتمع']),
                'plan_id' => 1,
                'is_enabled' => true,
            ],
            [
                'name' => json_encode(['en' => 'Insights and Analytics', 'ar' => 'الرؤى والتحليلات']),
                'plan_id' => 1,
                'is_enabled' => true,
            ],
            [
                'name' => json_encode(['en' => 'Mawj Rating', 'ar' => 'تقييم "Mawj"']),
                'plan_id' => 1,
                'is_enabled' => true,
            ],
            [
                'name' => json_encode(['en' => 'Monthly Mawj Awards', 'ar' => 'جوائز شهرية من "Mawj"']),
                'plan_id' => 1,
                'is_enabled' => true,
            ],
            [
                'name' => json_encode(['en' => '24/7 Support', 'ar' => 'دعم على مدار الساعة']),
                'plan_id' => 1,
                'is_enabled' => true,
            ],
            [
                'name' => json_encode(['en' => 'Tender Alerts', 'ar' => 'تنبيهات المناقصات']),
                'plan_id' => 1,
                'is_enabled' => true,
            ],
        ];

        DB::table('features')->insert($plan1_features);

        // Plan 2 Features (Premium) - Insert all Plan 1 features but with plan_id = 2
        $plan2_features = array_map(function ($feature) {
            $feature['plan_id'] = 2; // Change plan_id to 2 (Premium)
            return $feature;
        }, $plan1_features);

        // Add additional features for Plan 2
        $plan2_features = array_merge($plan2_features, [
            [
                'name' => json_encode(['en' => 'Job Listings', 'ar' => 'إدراج الوظائف']),
                'plan_id' => 2, // Plan 2
                'is_enabled' => true,
            ],
            [
                'name' => json_encode(['en' => 'Lead Generation from Campaigns', 'ar' => 'توليد العملاء المحتملين من الحملات']),
                'plan_id' => 2, // Plan 2
                'is_enabled' => true,
            ],
        ]);

        DB::table('features')->insert($plan2_features);

        // Plan 3 Features (Exclusive) - Insert all Plan 2 features but with plan_id = 3
        $plan3_features = array_map(function ($feature) {
            $feature['plan_id'] = 3; // Change plan_id to 3 (Exclusive)
            return $feature;
        }, $plan2_features);

        // Add additional features for Plan 3
        $plan3_features = array_merge($plan3_features, [
            [
                'name' => json_encode(['en' => 'Double Campaign Performance', 'ar' => 'أداء الحملات مضاعف']),
                'plan_id' => 3, // Plan 3
                'is_enabled' => true,
            ],
            [
                'name' => json_encode(['en' => 'Free Marketing Consultation', 'ar' => 'استشارة مجانية في التسويق']),
                'plan_id' => 3, // Plan 3
                'is_enabled' => true,
            ],
            [
                'name' => json_encode(['en' => 'Homepage Ads (2 per month)', 'ar' => 'إعلانات على الصفحة الرئيسية (2 شهريًا)']),
                'plan_id' => 3, // Plan 3
                'is_enabled' => true,
            ],
        ]);

        DB::table('features')->insert($plan3_features);
    }
}
