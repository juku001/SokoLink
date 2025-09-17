<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Payment; // assuming you have a payments table

class AirtelAPI
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $baseUrl;
    protected float $amount;
    protected string $phoneNumber;

    public function __construct(float $amount, string $phoneNumber)
    {
        $this->amount = $amount;
        $this->phoneNumber = $phoneNumber;
        $this->baseUrl = rtrim(env('AIRTEL_BASE_URL'), '/') . '/';
        $this->clientId = env('AIRTEL_CLIENT_ID');
        $this->clientSecret = env('AIRTEL_CLIENT_SECRET');
    }

    public function charge()
    {
        $token = $this->authenticateToken();
        if (!$token) {
            return [
                'status' => false,
                'message' => 'Failed to authenticate with Airtel API',
            ];
        }

        $ref = $this->generateReference();
        $url = $this->baseUrl . 'merchant/v1/payments/';

        $data = [
            "reference" => $ref,
            "subscriber" => [
                "country" => "TZ",
                "currency" => "TZS",
                "msisdn" => $this->phoneNumber,
            ],
            "transaction" => [
                "amount" => $this->amount,
                "country" => "TZ",
                "currency" => "TZS",
                "id" => $ref,
            ],
        ];

        $response = Http::withHeaders($this->getHeaders($token))
            ->post($url, $data);

        return [
            'dt' => $response->json(),
            'ref' => $ref
        ];
    }

    private function authenticateToken(): ?string
    {
        $url = $this->baseUrl . 'auth/oauth2/token';

        $data = [
            "client_id" => $this->clientId,
            "client_secret" => $this->clientSecret,
            "grant_type" => "client_credentials",
        ];

        $response = Http::withHeaders($this->getHeaders())
            ->post($url, $data);
        if ($response->successful()) {
            return $response->json()['access_token'] ?? null;
        }

        return null;
    }

    private function getHeaders(?string $token = null): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'X-Country' => 'TZ',
            'X-Currency' => 'TZS',
        ];
        if ($token) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }
        return $headers;
    }

    private function generateReference(): string
    {
        do {
            $ref = 'SL' . mt_rand(10000000, 99999999);
        } while (Payment::where('reference', $ref)->exists());

        return $ref;
    }
}
