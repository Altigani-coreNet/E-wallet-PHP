<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    /**
     * Static support contact info (replace with settings/CMS later).
     */
    public function getSupportInfo(Request $request): JsonResponse
    {
        $lang = app()->getLocale();
        if (! in_array($lang, ['en', 'ar'], true)) {
            $lang = 'en';
        }

        $isArabic = $lang === 'ar';

        return response()->json([
            'success' => true,
            'message' => $isArabic ? 'تم جلب بيانات الدعم بنجاح' : 'Support information retrieved successfully',
            'data' => [
                'language' => $lang,
                'title' => $isArabic ? 'تحتاج مساعدة؟' : 'Need assistance?',
                'description' => $isArabic
                    ? 'إذا كان لديك أي أسئلة، فريق الدعم جاهز لمساعدتك.'
                    : 'If you have any questions, our support team is here to help.',
                'email' => 'support@corenetpay.com',
                'phone' => '+971 50 123 4567',
                'whatsapp' => '+971501234567',
                'live_chat_url' => 'https://corenettech.com/support',
                'working_hours' => $isArabic
                    ? 'الإثنين - الجمعة: 9:00 - 18:00 (توقيت الخليج)'
                    : 'Monday - Friday: 9:00 AM - 6:00 PM (GST)',
                'retrieved_at' => now()->toISOString(),
            ],
        ], 200);
    }
}
