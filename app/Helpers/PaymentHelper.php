<?php
namespace App\Helpers;

use App\Models\PaymentMethod;
use App\Services\AirtelAPI;

class PaymentHelper
{


    public function __construct()
    {

    }


    /**
     * Initiates a payment based on method and type.
     *
     * @param PaymentMethod $paymentMethod
     * @param array $data (e.g., phone, amount, order_id, etc.)
     * @return array
     */
    public static function initiatePayment(PaymentMethod $paymentMethod, array $data)
    {



        switch ($paymentMethod->type) {
            case 'mno': // Mobile Network Operator
                return self::handleMno($paymentMethod, $data);

            case 'card':
                return [
                    'status' => false,
                    'message' => 'Card payments are not configured yet.'
                ];

            case 'bank':
                return [
                    'status' => false,
                    'message' => 'Bank payments are not configured yet.'
                ];

            default:
                return [
                    'status' => false,
                    'message' => 'Unknown payment type.'
                ];
        }
    }

    /**
     * Handle MNO payments.
     * For now, only Airtel is supported.
     */
    private static function handleMno(PaymentMethod $paymentMethod, array $data)
    {

        switch ($paymentMethod->code) {
            case env('PAY_METHOD_AIRTEL'):
                return self::simulateAirtelPayment($data);

            case env('PAY_METHOD_VODA'):
            case env('PAY_METHOD_TIGO'):
            case env('PAY_METHOD_HALOTEL'):
            case env('PAY_METHOD_TTCL'):
                return [
                    'status' => false,
                    'message' => ucfirst($paymentMethod->display) . ' not configured yet.'
                ];
            default:
                return [
                    'status' => false,
                    'message' => 'Mobile network not recognized.'
                ];
        }
    }

    /**
     * Simulate Airtel Money payment.
     * Later you can replace with actual API integration.
     */
    private static function simulateAirtelPayment(array $data)
    {
        if (empty($data['phone']) || empty($data['amount'])) {
            return [
                'status' => false,
                'message' => 'Phone number and amount are required for Airtel Money payment.'
            ];
        }

        $airtelAPIService = new AirtelAPI(
            $data['amount'],
            $data['phone']
        );
        // return $airtelAPIService->charge();

        $success = true;

        if ($success) {
            return [
                'status' => true,
                'reference'=> 'some',
                'message' => 'Payment initiated successful'
            ];
        }

        return [
            'status' => false,
            'message' => 'Failed to initiate payment.'
        ];
    }
}
