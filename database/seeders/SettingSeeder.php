<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Contract Terms - English
            [
                'key' => 'contract_terms_en',
                'value' => '<h2>Merchant Agreement Terms and Conditions</h2>

<h3>1. Agreement Overview</h3>
<p>This Merchant Agreement ("Agreement") is entered into between the Merchant and our Payment Processing Service ("Service Provider"). By signing this agreement, the Merchant agrees to the following terms and conditions.</p>

<h3>2. Services Provided</h3>
<p>The Service Provider agrees to provide the following services:</p>
<ul>
    <li>Payment processing for credit/debit card transactions</li>
    <li>Point of Sale (POS) terminal equipment</li>
    <li>Online payment gateway integration</li>
    <li>Monthly transaction reporting and analytics</li>
    <li>24/7 technical support</li>
    <li>Fraud prevention and chargeback management</li>
</ul>

<h3>3. Merchant Responsibilities</h3>
<p>The Merchant agrees to:</p>
<ul>
    <li>Maintain valid business licenses and permits</li>
    <li>Provide accurate business information</li>
    <li>Comply with all applicable laws and regulations</li>
    <li>Safeguard POS equipment and credentials</li>
    <li>Notify Service Provider of any suspicious activities</li>
    <li>Maintain adequate security measures for customer data</li>
</ul>

<h3>4. Fees and Charges</h3>
<p>The Merchant agrees to pay the following fees:</p>
<ul>
    <li>Transaction fees as specified in the Service Fee Schedule</li>
    <li>Monthly subscription fees (if applicable)</li>
    <li>Equipment rental or purchase fees</li>
    <li>Chargeback fees when applicable</li>
    <li>Any additional services requested</li>
</ul>

<h3>5. Settlement Terms</h3>
<p>Transaction settlements will be processed according to the agreed schedule, typically within 1-3 business days. The Service Provider reserves the right to hold funds in case of suspected fraud or chargebacks.</p>

<h3>6. Data Protection and Privacy</h3>
<p>Both parties agree to comply with all applicable data protection regulations, including but not limited to GDPR and PCI DSS standards. Customer payment information will be handled securely and confidentially.</p>

<h3>7. Term and Termination</h3>
<p>This Agreement shall remain in effect for the duration specified in the Service Details section. Either party may terminate this agreement with 30 days written notice. Upon termination, all equipment must be returned and final settlements processed.</p>

<h3>8. Liability and Indemnification</h3>
<p>The Service Provider shall not be liable for any indirect, incidental, or consequential damages. The Merchant agrees to indemnify the Service Provider against any claims arising from the Merchant\'s use of the services.</p>

<h3>9. Dispute Resolution</h3>
<p>Any disputes arising from this Agreement shall be resolved through arbitration in accordance with local laws and regulations.</p>

<h3>10. Agreement Acceptance</h3>
<p>By signing below, the Merchant acknowledges that they have read, understood, and agree to be bound by all terms and conditions of this Agreement.</p>',
                'type' => 'html',
                'group' => 'contracts',
                'description' => 'Contract terms and conditions in English'
            ],
            
            // Contract Terms - Arabic
            [
                'key' => 'contract_terms_ar',
                'value' => '<h2>شروط وأحكام اتفاقية التاجر</h2>

<h3>١. نظرة عامة على الاتفاقية</h3>
<p>يتم إبرام اتفاقية التاجر هذه ("الاتفاقية") بين التاجر وخدمة معالجة الدفع ("مزود الخدمة"). بتوقيع هذه الاتفاقية، يوافق التاجر على الشروط والأحكام التالية.</p>

<h3>٢. الخدمات المقدمة</h3>
<p>يوافق مزود الخدمة على تقديم الخدمات التالية:</p>
<ul>
    <li>معالجة الدفع لمعاملات البطاقات الائتمانية/الخصم</li>
    <li>معدات نقطة البيع (POS)</li>
    <li>تكامل بوابة الدفع عبر الإنترنت</li>
    <li>تقارير وتحليلات المعاملات الشهرية</li>
    <li>الدعم الفني على مدار الساعة طوال أيام الأسبوع</li>
    <li>الوقاية من الاحتيال وإدارة رد المبالغ المدفوعة</li>
</ul>

<h3>٣. مسؤوليات التاجر</h3>
<p>يوافق التاجر على:</p>
<ul>
    <li>الحفاظ على تراخيص وتصاريح العمل الصالحة</li>
    <li>تقديم معلومات تجارية دقيقة</li>
    <li>الامتثال لجميع القوانين واللوائح المعمول بها</li>
    <li>حماية معدات نقاط البيع وبيانات الاعتماد</li>
    <li>إخطار مزود الخدمة بأي أنشطة مشبوهة</li>
    <li>الحفاظ على تدابير أمنية كافية لبيانات العملاء</li>
</ul>

<h3>٤. الرسوم والمصاريف</h3>
<p>يوافق التاجر على دفع الرسوم التالية:</p>
<ul>
    <li>رسوم المعاملات كما هو محدد في جدول رسوم الخدمة</li>
    <li>رسوم الاشتراك الشهرية (إن وجدت)</li>
    <li>رسوم استئجار أو شراء المعدات</li>
    <li>رسوم رد المبالغ المدفوعة عند الاقتضاء</li>
    <li>أي خدمات إضافية مطلوبة</li>
</ul>

<h3>٥. شروط التسوية</h3>
<p>ستتم معالجة تسويات المعاملات وفقًا للجدول المتفق عليه، عادةً في غضون ١-٣ أيام عمل. يحتفظ مزود الخدمة بالحق في حجز الأموال في حالة الاشتباه في احتيال أو رد المبالغ المدفوعة.</p>

<h3>٦. حماية البيانات والخصوصية</h3>
<p>يوافق الطرفان على الامتثال لجميع لوائح حماية البيانات المعمول بها، بما في ذلك على سبيل المثال لا الحصر اللائحة العامة لحماية البيانات ومعايير PCI DSS. سيتم التعامل مع معلومات دفع العملاء بشكل آمن وسري.</p>

<h3>٧. المدة والإنهاء</h3>
<p>تظل هذه الاتفاقية سارية المفعول للمدة المحددة في قسم تفاصيل الخدمة. يجوز لأي من الطرفين إنهاء هذه الاتفاقية بإشعار كتابي مدته ٣٠ يومًا. عند الإنهاء، يجب إرجاع جميع المعدات ومعالجة التسويات النهائية.</p>

<h3>٨. المسؤولية والتعويض</h3>
<p>لا يتحمل مزود الخدمة المسؤولية عن أي أضرار غير مباشرة أو عرضية أو تبعية. يوافق التاجر على تعويض مزود الخدمة ضد أي مطالبات ناشئة عن استخدام التاجر للخدمات.</p>

<h3>٩. حل النزاعات</h3>
<p>يتم حل أي نزاعات ناشئة عن هذه الاتفاقية من خلال التحكيم وفقًا للقوانين واللوائح المحلية.</p>

<h3>١٠. قبول الاتفاقية</h3>
<p>بالتوقيع أدناه، يقر التاجر بأنه قد قرأ وفهم ويوافق على الالتزام بجميع شروط وأحكام هذه الاتفاقية.</p>',
                'type' => 'html',
                'group' => 'contracts',
                'description' => 'Contract terms and conditions in Arabic'
            ],
            
            // System Settings
            [
                'key' => 'transaction_fee',
                'value' => '2.5',
                'type' => 'number',
                'group' => 'fees',
                'description' => 'Transaction fee percentage'
            ],
            [
                'key' => 'settlement_period',
                'value' => 'T+1 (Next Business Day)',
                'type' => 'text',
                'group' => 'system',
                'description' => 'Settlement period for transactions'
            ],
            [
                'key' => 'contract_duration',
                'value' => '12',
                'type' => 'number',
                'group' => 'contracts',
                'description' => 'Default contract duration in months'
            ],
            [
                'key' => 'payment_methods',
                'value' => '["Visa", "Mastercard", "Mada", "Apple Pay", "STC Pay"]',
                'type' => 'json',
                'group' => 'system',
                'description' => 'Supported payment methods'
            ]
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('Settings seeded successfully!');
    }
}

