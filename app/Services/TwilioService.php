<?php

namespace App\Services;

use Twilio\Rest\Client;
use Exception;
use Illuminate\Support\Facades\Log;

class TwilioService
{
    protected $client;
    protected $from;

    public function __construct()
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $this->from = config('services.twilio.from');

        if (!$sid || !$token) {
            throw new Exception('Twilio credentials not configured. Please set TWILIO_SID and TWILIO_TOKEN in your .env file.');
        }

        $this->client = new Client($sid, $token);
    }

    /**
     * Send SMS message using Twilio
     *
     * @param string $to Phone number to send to (with country code, e.g. +971XXXXXXXXX)
     * @param string $message Message content
     * @param string|null $from Override default from number
     * @return array
     */
    public function sendSms(string $to, string $message, string $from = null): array
    {
        try {
            $fromNumber = $from ?? $this->from;
            
            if (!$fromNumber) {
                throw new Exception('Twilio from number not configured. Please set TWILIO_FROM in your .env file.');
            }

            $message = $this->client->messages->create(
                $to,
                [
                    "from" => $fromNumber,
                    "body" => $message
                ]
            );

            return [
                'success' => true,
                'message_sid' => $message->sid,
                'status' => $message->status,
                'to' => $message->to,
                'from' => $message->from,
                'body' => $message->body,
                'date_created' => $message->dateCreated->format('Y-m-d H:i:s'),
            ];

        } catch (Exception $e) {
            Log::error('Twilio SMS Error: ' . $e->getMessage(), [
                'to' => $to,
                'message' => $message,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode() ?? null,
            ];
        }
    }

    /**
     * Send OTP SMS message
     *
     * @param string $to Phone number to send to (with country code, e.g. +971XXXXXXXXX)
     * @param string $otp OTP code
     * @param string|null $from Override default from number
     * @return array
     */
    public function sendOtpSms(string $to, string $otp, string $from = null): array
    {
        $message = "Your OTP code is: {$otp}";
        return $this->sendSms($to, $message, $from);
    }

    /**
     * Get message status by SID
     *
     * @param string $messageSid
     * @return array
     */
    public function getMessageStatus(string $messageSid): array
    {
        try {
            $message = $this->client->messages($messageSid)->fetch();

            return [
                'success' => true,
                'message_sid' => $message->sid,
                'status' => $message->status,
                'to' => $message->to,
                'from' => $message->from,
                'body' => $message->body,
                'date_created' => $message->dateCreated->format('Y-m-d H:i:s'),
                'date_updated' => $message->dateUpdated->format('Y-m-d H:i:s'),
                'error_code' => $message->errorCode,
                'error_message' => $message->errorMessage,
            ];

        } catch (Exception $e) {
            Log::error('Twilio Get Message Status Error: ' . $e->getMessage(), [
                'message_sid' => $messageSid,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode() ?? null,
            ];
        }
    }

    /**
     * Get account information
     *
     * @return array
     */
    public function getAccountInfo(): array
    {
        try {
            $account = $this->client->api->accounts($this->client->getAccountSid())->fetch();

            return [
                'success' => true,
                'account_sid' => $account->sid,
                'friendly_name' => $account->friendlyName,
                'status' => $account->status,
                'type' => $account->type,
            ];

        } catch (Exception $e) {
            Log::error('Twilio Get Account Info Error: ' . $e->getMessage(), [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode() ?? null,
            ];
        }
    }
}
