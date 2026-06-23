<?php

namespace App\Services;

class SMSService
{
  
        public function send(array $data): bool
        {
            $phone = ltrim($data['identifier'], "0");
            $otp = $data['otp'];
    
            if (Str::startsWith($phone, '0')) {
                $phone = Str::substr($phone, 1);
            }
    
            $sms_text = ($otp . ' is your OTP for MAWJ Vendor Login. Thanks for using MAWJ');
            $url = "https://user.digitizebirdsms.com/api/v2/SendSMS";
    
            // API Parameters
            $params = [
                'ApiKey' => 'JMcHzlx0h2Vnc4wO4NA93Tb5bji8keNunVmcfBQK3Bg=',
                'ClientId' => '0ada3c39-a49f-44a7-9442-fe3662815a50',
                'SenderId' => 'MAWJ',
                'Message' => $sms_text,
                'MobileNumbers' => '971' . $phone,
            ];
    
            // Sending the request with SSL verification disabled
            if (MawjSetting::where("key", "sms_notification")->first()?->value) {
                $response = Http::withOptions([
                    'verify' => false, // Disable SSL verification
                ])->get($url, $params);
    
                return $response->json()["ErrorCode"] == 0;
            }
    
            return false;
        }
    }
