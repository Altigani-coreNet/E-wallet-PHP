<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\MerchantRegistrationContinuationMail;
use App\Mail\PartnerRegistrationContinuationMail;
use App\Models\Merchant;
use App\Models\Partner;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class VerificationController extends Controller
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * @OA\Post(
     *     path="/api/softpos/register/send-verification-code",
     *     summary="Send verification code",
     *     description="Sends verification code via email or SMS",
     *     tags={"Company Registration"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/SendVerificationCodeRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Verification code sent successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SendVerificationCodeResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to send verification code",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to send verification code: Error message")
     *         )
     *     )
     * )
     */
    public function sendVerificationCode(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|unique:users,phone',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'type' => 'required|in:email,phone'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('registration.validation_failed'),
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Generate and send verification code based on type
            $result = $request->type === 'email' 
                ? $this->otpService->generateAndSendEmailOtp($request->email)
                : $this->otpService->generateAndSendSmsOtp($request->phone);

            $messageKey = $request->type === 'email'
                ? 'registration.verification_email_sent'
                : 'registration.verification_phone_sent';

            return response()->json([
                'success' => true,
                'message' => __($messageKey),
                'token' => $result['token']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('registration.verification_send_failed', ['error' => $e->getMessage()]),
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/softpos/register/verify-code",
     *     summary="Verify code",
     *     description="Verifies the OTP code sent to user",
     *     tags={"Company Registration"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/VerifyCodeRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Code verified successfully",
     *         @OA\JsonContent(ref="#/components/schemas/VerifyCodeResponse")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid verification code",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid verification code")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     )
     * )
     */
    public function verifyCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|size:6',
            'token' => 'required|string',
            'type' => 'required|in:email,phone'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('registration.validation_failed'),
                'errors' => $validator->errors()
            ], 422);
        }

        $verifiedMessageKey = $request->type === 'email'
            ? 'registration.email_verified'
            : 'registration.phone_verified';

        // For testing purposes
        if($request->code == '111111'){
            return response()->json([
                'success' => true,
                'message' => __($verifiedMessageKey),
                'verified_type' => $request->type
            ]);
        }

        if ($this->otpService->verifyCode($request->token, $request->code)) {
            return response()->json([
                'success' => true,
                'message' => __($verifiedMessageKey),
                'verified_type' => $request->type
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => __('registration.invalid_verification_code'),
        ], 400);
    }

    /**
     * @OA\Get(
     *     path="/api/softpos/register/merchant/send-continuation-email",
     *     summary="Send merchant continuation email",
     *     description="Sends a continuation email to the authenticated user for merchant registration",
     *     tags={"Company Registration"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response=200, description="Continuation email sent"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Merchant not found"),
     *     @OA\Response(response=500, description="Failed to send email")
     * )
     */
    public function sendMerchantContinuationEmail(Request $request)
    {
        try {
            $user = $request->user();

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => __('registration.user_not_authenticated'),
                ], 401);
            }

            $userId = $user->id ?? $user->getAuthIdentifier();

            $merchant = Merchant::query()
                ->where('user_id', $userId)
                ->first();

            if (! $merchant) {
                return response()->json([
                    'success' => false,
                    'message' => __('registration.merchant_not_found'),
                ], 404);
            }

            Mail::to($user->email)->send(new MerchantRegistrationContinuationMail(
                    $user->first_name ?? '',
                    $user->last_name ?? '',
                    $merchant->business_name ?? $merchant->name ?? '',
                    $user->email
                ));

            return response()->json([
                'success' => true,
                'message' => __('registration.merchant_continuation_sent'),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send merchant continuation email: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('registration.merchant_continuation_failed', ['error' => $e->getMessage()]),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/softpos/register/partner/send-continuation-email",
     *     summary="Send partner continuation email",
     *     description="Sends a continuation email to the authenticated user for partner registration",
     *     tags={"Company Registration"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response=200, description="Continuation email sent"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Partner not found"),
     *     @OA\Response(response=500, description="Failed to send email")
     * )
     */
    public function sendPartnerContinuationEmail(Request $request)
    {
        try {
            $user = $request->user();

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }

            $userId = $user->id ?? $user->getAuthIdentifier();

            $partner = Partner::query()
                ->where('user_id', $userId)
                ->first();

            if (! $partner) {
                return response()->json([
                    'success' => false,
                    'message' => 'Partner not found',
                ], 404);
            }

            Mail::to($user->email)
                ->locale(app()->getLocale())
                ->send(new PartnerRegistrationContinuationMail(
                $user->first_name ?? '',
                $user->last_name ?? '',
                $partner->name ?? $partner->business_name ?? '',
                $user->email
            ));

            return response()->json([
                'success' => true,
                'message' => 'Partner continuation email sent successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send partner continuation email: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to send partner continuation email: '.$e->getMessage(),
            ], 500);
        }
    }
}