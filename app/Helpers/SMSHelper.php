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

            $data = [
                "channel" => [
                    "channel" => env('SMARTNOLOGY_CLIENT_ID'),
                    "password" => env('SMARTNOLOGY_CLIENT_SECRET')
                ],
                "messages" => [
                    [
                        "text" => $message,
                        "msisdn" => $to,
                        "source" => env('SMARTNOLOGY_SENDER_ID')
                    ]
                ]
            ];

            $response = Http::post('https://bulksms.fasthub.co.tz/fasthub/messaging/json/api', $data);

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
