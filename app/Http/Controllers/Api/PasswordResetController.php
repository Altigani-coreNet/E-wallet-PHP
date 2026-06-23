<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetSuccessMail;
use App\Models\User;
use App\Models\UsersOtp;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class PasswordResetController extends Controller
{
    protected OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    public function requestReset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Always respond success for security
        $user = User::where('email', $request->email)->first();

        if ($user) {
            $displayName = trim(($user->name ?? '') . ' ' . ($user->last_name ?? ''));
            $result = $this->otpService->generateAndSendEmailOtp(
                $request->email,
                'password_reset',
                $displayName !== '' ? $displayName : null
            );
        } else {
            $result = [
                'token' => \Illuminate\Support\Str::random(64),
                'code' => \Illuminate\Support\Str::random(6),
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'A reset code has been sent to your email',
            'token' => $result['token'],
        ]);
    }

    public function verifyCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $otp = UsersOtp::where('token', $request->token)
                        ->where('code', $request->code)
                        ->where('expires_at', '>', now())
                        ->first();

        if (!$otp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired verification code',
            ], 400);
        }

        if ($otp->is_verified) {
            return response()->json([
                'success' => true,
                'message' => 'Verification code already confirmed',
            ]);
        }

        if (!$this->otpService->verifyCode($request->token, $request->code)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired verification code',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Verification code confirmed',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            // 'code' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $otp = UsersOtp::where('token', $request->token)
                        // ->where('code', $request->code)
                        ->where('expires_at', '>', now())
                        ->first();

        if (!$otp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired verification code',
            ], 400);
        }

        if (!$otp->is_verified) {
            if (!$this->otpService->verifyCode($request->token, $request->code)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired verification code',
                ], 400);
            }
            $otp->refresh();
        }

        $email = $otp->phone_number; // Email is stored in phone_number field
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        // Update password
        $user->password = Hash::make($request->password);
        $user->save();

        $this->otpService->consumeOtpById($otp->id);

        // Send password reset success email
        try {   
            Mail::to($user->email)->send(new PasswordResetSuccessMail($user));
        } catch (\Throwable $mailError) {
            \Log::warning('Failed to send PasswordResetSuccessMail: ' . $mailError->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully',
        ]);
    }
}

