<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TopupTestController extends Controller
{
    use ApiResponse;

    private function isArabic(Request $request): bool
    {
        $lang = strtolower((string) $request->header('Accept-Language', 'en'));
        return str_starts_with($lang, 'ar');
    }

    public function prepaid(Request $request): JsonResponse
    {
        $isArabic = $this->isArabic($request);
        $validator = Validator::make($request->all(), [
            'phone_number' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->ErrorMessage(
                $isArabic ? 'رقم الهاتف مطلوب.' : 'phone_number is required.',
                'TOPUP_VALIDATION_ERROR',
                422
            );
        }

        try {
            return $this->SuccessMessage([
                'topup_type' => 'prepaid',
                'message' => $isArabic ? 'تم بنجاح' : 'Done',
                'status' => 'success',
            ]);
        } catch (\Throwable $e) {
            return $this->ErrorMessage(
                ($isArabic ? 'فشل تنفيذ شحن مسبق الدفع: ' : 'Failed to process prepaid topup: ') . $e->getMessage(),
                'PREPAID_TOPUP_TEST_ERROR',
                500
            );
        }
    }

    public function postpaid(Request $request): JsonResponse
    {
        $isArabic = $this->isArabic($request);
        $validator = Validator::make($request->all(), [
            'phone_number' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->ErrorMessage(
                $isArabic ? 'رقم الهاتف مطلوب.' : 'phone_number is required.',
                'TOPUP_VALIDATION_ERROR',
                422
            );
        }

        try {
            $phoneNumber = trim((string) $request->input('phone_number'));
            $amountDue = number_format((float) $request->input('amount_due', 120.00), 2, '.', '');
            $minAmount = 10;
            $maxAmount = (float) $amountDue;
            return response()->json([
                'status' => true,
                'screen_id' => 'postpaid_billing',
                'data' => [
                    'widgets' => [
                        [
                            'type' => 'billing_header_card',
                            'variant' => 'gradient_primary',
                            'content' => [
                                'title' => $isArabic ? 'المبلغ المستحق حالياً' : 'Current Amount Due',
                                'value' => $amountDue,
                                'currency' => 'AED',
                                'subtitle' => ($isArabic ? 'الهاتف: ' : 'Phone: ') . $phoneNumber,
                                'status_tag' => $isArabic ? 'قيد الانتظار' : 'Pending',
                            ],
                        ],
                        [
                            'type' => 'info_banner',
                            'variant' => 'warning',
                            'content' => [
                                'text' => $isArabic
                                    ? 'ستنتهي فاتورتك خلال 3 أيام لتجنب انقطاع الخدمة.'
                                    : 'Your bill will expire in 3 days to avoid service interruption.',
                            ],
                        ],
                    ],
                    'filed_roles' => [
                        [
                            'field_name' => 'amount',
                            'rules' => [
                                'required' => true,
                                'numeric' => true,
                                'min' => $minAmount,
                                'max' => $maxAmount,
                                // Accept integers or decimals with up to 3 digits after dot (e.g. 12, 12.23, 12.233)
                                'regex' => '^\d+(\.\d{1,3})?$',
                            ],
                            'messages' => [
                                'min' => $isArabic ? 'يجب ألا يقل المبلغ عن 10.' : 'Amount should not be less than 10.',
                                'max' => $isArabic ? 'يجب ألا يزيد المبلغ عن ' . $amountDue . '.' : 'Amount should not be greater than ' . $amountDue . '.',
                                'regex' => $isArabic ? 'يجب أن يكون المبلغ رقماً صالحاً مثل 12.233 أو 12123.12.' : 'Amount must be a valid number like 12.233 or 12123.12.',
                            ],
                        ],
                        [
                            'field_name' => 'note',
                            'rules' => [
                                'required' => false,
                                'string' => true,
                                'max_length' => 250,
                                // Allow letters, numbers, spaces and common punctuation.
                                'regex' => "^[A-Za-z0-9\\s\\-_,.()!?':;/@#&+]*$",
                            ],
                            'messages' => [
                                'max_length' => $isArabic ? 'يجب ألا تتجاوز الملاحظة 250 حرفاً.' : 'Note must not exceed 250 characters.',
                                'regex' => $isArabic ? 'تحتوي الملاحظة على أحرف غير صالحة.' : 'Note contains invalid characters.',
                            ],
                        ],
                    ]
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->ErrorMessage(
                ($isArabic ? 'فشل تحميل استجابة فاتورة لاحقة الدفع: ' : 'Failed to load postpaid provider response: ') . $e->getMessage(),
                'POSTPAID_TOPUP_TEST_ERROR',
                500
            );
        }
    }

    public function invoice(Request $request): JsonResponse
    {
        $isArabic = $this->isArabic($request);
        $validator = Validator::make($request->all(), [
            'student_id' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->ErrorMessage(
                $isArabic ? 'رقم الطالب مطلوب.' : 'student_id is required.',
                'TOPUP_VALIDATION_ERROR',
                422
            );
        }

        try {
            $studentId = trim((string) $request->input('student_id'));

            // Demo static lookup by student_id (replace with DB/provider integration later).
            $studentDataset = [
                'STU-1001' => [
                    'student_name' => 'Ahmed Ali',
                    'student_email' => 'ahmed.ali@example.com',
                    'sdn_no' => 'SDN-2026-00123',
                    'account_number' => 'ACC-778899',
                    'college' => 'College of Engineering',
                    'university' => 'FastPay University',
                    'phone_number' => '249912345678',
                    'amount_due' => 120.00,
                    'currency' => 'AED',
                ],
                'STU-1002' => [
                    'student_name' => 'Sara Mohamed',
                    'student_email' => 'sara.mohamed@example.com',
                    'sdn_no' => 'SDN-2026-00456',
                    'account_number' => 'ACC-991122',
                    'college' => 'College of Business',
                    'university' => 'FastPay University',
                    'phone_number' => '249955566677',
                    'amount_due' => 95.50,
                    'currency' => 'AED',
                ],
            ];

            $studentInfo = $studentDataset[$studentId] ?? [
                'student_name' => 'Student Name',
                'student_email' => 'student@example.com',
                'sdn_no' => 'SDN-00001',
                'account_number' => 'ACC-123456',
                'college' => 'College of Engineering',
                'university' => 'FastPay University',
                'phone_number' => '249900000000',
                'amount_due' => 120.00,
                'currency' => 'AED',
            ];

            $phoneNumber = $studentInfo['phone_number'];
            $studentName = $studentInfo['student_name'];
            $studentEmail = $studentInfo['student_email'];
            $sdnNo = $studentInfo['sdn_no'];
            $accountNumber = $studentInfo['account_number'];
            $college = $studentInfo['college'];
            $university = $studentInfo['university'];
            $currency = $studentInfo['currency'];
            $amountDue = number_format((float) $studentInfo['amount_due'], 2, '.', '');

            return response()->json([
                'status' => true,
                'screen_id' => 'invoice_details',
                'data' => [
                    'amount' => $amountDue,
                    'currency' => $currency,
                    'widgets' => [
                        [
                            'type' => 'billing_header_card',
                            'variant' => 'gradient_primary',
                            'content' => [
                                'title' => $isArabic ? 'قيمة الفاتورة' : 'Invoice Amount',
                                'value' => $amountDue,
                                'currency' => $currency,
                                'subtitle' => ($isArabic ? 'رقم الطالب: ' : 'Student ID: ') . $studentId . ' | ' . ($isArabic ? 'الهاتف: ' : 'Phone: ') . $phoneNumber,
                                'status_tag' => $isArabic ? 'قيد الانتظار' : 'Pending',
                            ],
                        ],
                        [
                            'type' => 'card_table',
                            'variant' => 'default',
                            'content' => [
                                'title' => $isArabic ? 'تفاصيل فاتورة الطالب' : 'Student Invoice Details',
                                'rows' => [
                                    ['field' => $isArabic ? 'الاسم' : 'Student Name', 'value' => $studentName],
                                    ['field' => $isArabic ? 'البريد الإلكتروني' : 'Email', 'value' => $studentEmail],
                                    ['field' => $isArabic ? 'رقم SDN' : 'SDN No', 'value' => $sdnNo],
                                    ['field' => $isArabic ? 'الحساب' : 'Account', 'value' => $accountNumber],
                                    ['field' => $isArabic ? 'الكلية' : 'College', 'value' => $college],
                                    ['field' => $isArabic ? 'الجامعة' : 'University', 'value' => $university],
                                    ['field' => $isArabic ? 'المبلغ' : 'Amount', 'value' => $amountDue, 'currency' => $currency],
                                ],
                            ],
                        ],
                    ],
                    'filed_roles' => [
                        [
                            'field_name' => 'amount',
                            'rules' => [
                                'required' => true,
                                'numeric' => true,
                                'min' => 10,
                                'max' => (float) $amountDue,
                                'regex' => '^\d+(\.\d{1,3})?$',
                            ],
                            'messages' => [
                                'min' => $isArabic ? 'يجب ألا يقل المبلغ عن 10.' : 'Amount should not be less than 10.',
                                'max' => $isArabic ? 'يجب ألا يزيد المبلغ عن ' . $amountDue . '.' : 'Amount should not be greater than ' . $amountDue . '.',
                                'regex' => $isArabic ? 'يجب أن يكون المبلغ رقماً صالحاً مثل 12.233 أو 12123.12.' : 'Amount must be a valid number like 12.233 or 12123.12.',
                            ],
                        ],
                        [
                            'field_name' => 'note',
                            'rules' => [
                                'required' => false,
                                'string' => true,
                                'max_length' => 250,
                                'regex' => "^[A-Za-z0-9\\s\\-_,.()!?':;/@#&+]*$",
                            ],
                            'messages' => [
                                'max_length' => $isArabic ? 'يجب ألا تتجاوز الملاحظة 250 حرفاً.' : 'Note must not exceed 250 characters.',
                                'regex' => $isArabic ? 'تحتوي الملاحظة على أحرف غير صالحة.' : 'Note contains invalid characters.',
                            ],
                        ],
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->ErrorMessage(
                ($isArabic ? 'فشل تحميل استجابة الفاتورة: ' : 'Failed to load invoice response: ') . $e->getMessage(),
                'INVOICE_TEST_ERROR',
                500
            );
        }
    }
}

