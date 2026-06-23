<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TwilioService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Exception;

class TwilioTestController extends Controller
{
    protected $twilioService;

    public function __construct(TwilioService $twilioService)
    {
        $this->twilioService = $twilioService;
    }

    /**
     * Test sending SMS
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendSms(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'to' => 'required|string|regex:/^\+[1-9]\d{1,14}$/',
            'message' => 'required|string|max:1600',
            'from' => 'nullable|string|regex:/^\+[1-9]\d{1,14}$/',
        ], [
            'to.required' => 'Phone number is required',
            'to.regex' => 'Phone number must be in international format (e.g., +971XXXXXXXXX)',
            'message.required' => 'Message is required',
            'message.max' => 'Message cannot exceed 1600 characters',
            'from.regex' => 'From number must be in international format (e.g., +971XXXXXXXXX)',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->twilioService->sendSms(
                $request->to,
                $request->message,
                $request->from
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'SMS sent successfully',
                    'data' => $result
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send SMS',
                    'error' => $result['error']
                ], 400);
            }

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while sending SMS',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test sending OTP SMS
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendOtpSms(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'to' => 'required|string|regex:/^\+[1-9]\d{1,14}$/',
            'otp' => 'required|string|min:4|max:10',
            'from' => 'nullable|string|regex:/^\+[1-9]\d{1,14}$/',
        ], [
            'to.required' => 'Phone number is required',
            'to.regex' => 'Phone number must be in international format (e.g., +971XXXXXXXXX)',
            'otp.required' => 'OTP code is required',
            'otp.min' => 'OTP must be at least 4 characters',
            'otp.max' => 'OTP cannot exceed 10 characters',
            'from.regex' => 'From number must be in international format (e.g., +971XXXXXXXXX)',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->twilioService->sendOtpSms(
                $request->to,
                $request->otp,
                $request->from
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'OTP SMS sent successfully',
                    'data' => $result
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send OTP SMS',
                    'error' => $result['error']
                ], 400);
            }

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while sending OTP SMS',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get message status by SID
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getMessageStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message_sid' => 'required|string',
        ], [
            'message_sid.required' => 'Message SID is required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->twilioService->getMessageStatus($request->message_sid);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Message status retrieved successfully',
                    'data' => $result
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to get message status',
                    'error' => $result['error']
                ], 400);
            }

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while getting message status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Twilio account information
     *
     * @return JsonResponse
     */
    public function getAccountInfo(): JsonResponse
    {
        try {
            $result = $this->twilioService->getAccountInfo();

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Account information retrieved successfully',
                    'data' => $result
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to get account information',
                    'error' => $result['error']
                ], 400);
            }

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while getting account information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test Twilio configuration
     *
     * @return JsonResponse
     */
    public function testConfiguration(): JsonResponse
    {
        try {
            $config = [
                'twilio_sid' => config('services.twilio.sid') ? 'Configured' : 'Not configured',
                'twilio_token' => config('services.twilio.token') ? 'Configured' : 'Not configured',
                'twilio_from' => config('services.twilio.from') ?: 'Not configured',
            ];

            $allConfigured = config('services.twilio.sid') && 
                           config('services.twilio.token') && 
                           config('services.twilio.from');

            return response()->json([
                'success' => true,
                'message' => $allConfigured ? 'Twilio is properly configured' : 'Twilio configuration incomplete',
                'data' => [
                    'configuration' => $config,
                    'is_configured' => $allConfigured,
                    'instructions' => $allConfigured ? null : [
                        'Add the following to your .env file:',
                        'TWILIO_SID=your_account_sid',
                        'TWILIO_TOKEN=your_auth_token',
                        'TWILIO_FROM=your_twilio_phone_number'
                    ]
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while checking configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
