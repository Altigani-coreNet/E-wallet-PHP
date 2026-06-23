<?php

namespace App\Repositories;

use App\Models\UsersOtp;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;

class OtpRepository
{
public function store($data)
    {
        $code = UsersOtp::generateOtpNumber();
        $token = Crypt::encrypt(date('Y_m_d') . $code);

        // Store either email or phone in phone_number field
        $identifier = $data['type'] === 'email' ? $data['email'] : $data['phone'];

        return UsersOtp::create([
            'phone_number' => $identifier, // Will store either email or phone
            'code' => $code,
            'token' => $token,
            'is_verified' => 1,
            'expires_at' => Carbon::now()->addMinutes(10) // Code expires in 10 minutes
        ]);
    }

    public function verifyCode($token, $code)
    {
        try {
            $decrypted = Crypt::decrypt($token);
            $date = substr($decrypted, 0, 10); // Get the date part
            
            if ($date !== date('Y_m_d')) {
                return false;
            }

            $otp = UsersOtp::where([
                'token' => $token,
                'code' => $code,
                'is_verified' => 1
            ])
            ->where('expires_at', '>', Carbon::now()) // Check if code hasn't expired
            ->first();

            if ($otp) {
                $otp->delete(); // Remove used code
                return true;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function findValidOtp(string $token, string $code): ?UsersOtp
    {
        try {
            $decrypted = Crypt::decrypt($token);
            $date = substr($decrypted, 0, 10);
            if ($date !== date('Y_m_d')) {
                return null;
            }

            return UsersOtp::where([
                'token' => $token,
                'code' => $code,
                // 'is_verified' => 1,
            ])->where('expires_at', '>', Carbon::now())->first();
        } catch (\Exception $e) {
            return null;
        }
    }

    public function deleteById(int $id): void
    {
        UsersOtp::where('id', $id)->delete();
    }

    public function findValidOtpByToken(string $token): ?UsersOtp
    {
        try {
            $decrypted = Crypt::decrypt($token);
            $date = substr($decrypted, 0, 10);
            if ($date !== date('Y_m_d')) {
                return null;
            }

            return UsersOtp::where([
                'token' => $token,
                // 'is_verified' => 1,
            ])->first();
        } catch (\Exception $e) {
            return null;
        }
    }
}