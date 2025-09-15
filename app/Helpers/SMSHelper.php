<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class SMSHelper
{
    /**
     * Send SMS via Smartnology API
     *
     * @param string $to
     * @param string $message
     * @return array
     */
    public static function send($to, $message)
    {
        try {
            $response = Http::get('https://sendus.smartnology.co.tz/api/v1/sms/api', [
                'action' => 'send-sms',
                'api_key' => env('SMARTNOLOGY_API_KEY'), // keep keys in .env
                'to' => $to,
                'from' => env('SMARTNOLOGY_SENDER_ID', 'SokoLink'),
                'sms' => $message,
            ]);

            if ($response->successful()) {
                return [
                    'status' => true,
                    'message' => 'SMS sent successfully.',
                    'data' => $response->json(),
                ];
            }

            return [
                'status' => false,
                'message' => 'Failed to send SMS.',
                'error' => $response->body(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'SMS sending error: ' . $e->getMessage(),
            ];
        }
    }
}
