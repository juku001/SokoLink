<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class PayoutHelper
{
    protected $sellerId;
    protected $amount;

    public function __construct($sellerId, $amount)
    {
        $this->sellerId = $sellerId;
        $this->amount = $amount;
    }

    /**
     * Initiate a payout to Airtel Money.
     *
     * @return array [
     *   'status' => bool,
     *   'message' => string,
     *   'transaction_id' => string|null
     * ]
     */
    public function initiatePayout(): array
    {
        try {
            $url = rtrim(env('AIRTEL_BASE_URL'), '/') . '/standard/v2/disbursements/';
            $data = $this->getRequestData();

            // Example POST request with Laravel HTTP client
            $response = Http::withHeaders($this->headers())
                ->post($url, $data);

            if (!$response->successful()) {
                return [
                    'status' => false,
                    'reference' => $data['reference'],
                    'message' => 'Airtel request failed with HTTP ' . $response->status(),
                    'transaction_id' => null
                ];
            }

            $json = $response->json();

            // Check Airtel's success flag
            $success = $json['status']['success'] ?? false;
            $message = $json['status']['message'] ?? 'Unknown response';

            return [
                'status' => (bool) $success,
                'message' => $message,
                'reference' => $data['reference'],
                'transaction_id' => $success
                    ? ($json['data']['transaction']['reference_id'] ?? null)
                    : null,
            ];

        } catch (\Throwable $e) {
            return [
                'status' => false,
                'reference' => $data['reference'],
                'message' => 'Exception: ' . $e->getMessage(),
                'transaction_id' => null,
            ];
        }
    }

    /**
     * Prepare Airtel request payload.
     */
    protected function getRequestData(): array
    {
        $user = User::with('seller')->findOrFail($this->sellerId);

        // Generate a unique reference for both 'reference' and 'id'
        $reference = 'REF-' . Str::uuid()->toString();

        return [
            'payee' => [
                'currency' => 'TZS',
                'msisdn' => $user->seller->payout_account ?? '', // phone/MSISDN
                'name' => $user->name,
            ],
            'reference' => $reference,
            'pin' => env('AIRTEL_DISBURSEMENT_PIN'),
            'transaction' => [
                'amount' => $this->amount,
                'id' => $reference,
                'type' => 'B2B',
            ],
        ];
    }

    /**
     * Build the headers required by Airtel.
     */
    protected function headers(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => '*/*',
            'X-Country' => env('AIRTEL_COUNTRY', 'UG'),
            'X-Currency' => env('AIRTEL_CURRENCY', 'UGX'),
            'Authorization' => 'Bearer ' . $this->getAccessToken(),
        ];
    }

    /**
     * Retrieve the Airtel API token (stub—implement your own token logic).
     */
    protected function getAccessToken(): string
    {
        // You might call another helper/service to refresh/get Airtel token.
        return cache('airtel_access_token');
    }
}
