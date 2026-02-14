<?php
namespace App\Helpers;

use App\Models\PaymentMethod;
use App\Services\AirtelAPI;

class PaymentHelper
{



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
        $data = $airtelAPIService->charge();
        $success = isset($data['dt']['data']['status']) && $data['dt']['data']['status'] === 'SUCCESS';
        if ($success) {
            return [
                'status' => true,
                'data' => $data,
                'reference' => $data['ref'] ?? null,
                'message' => 'Payment initiated successful'
            ];
        }

        return [
            'status' => false,
            'data' => $data,
            'message' => 'Failed to initiate payment.'
        ];

    }
}





// /usssd  /merchant/v1/payments/


// curl -X POST https://openapiuat.airtel.africa/merchant/v1/payments/
//   -H 'Accept: */* '
//   -H 'Content-Type: application/json'
//   -H 'X-Country: UG'
//   -H 'X-Currency: UGX'
//   -H 'Authorization: Bearer UC*******2w'




// {
//     "reference": "Testing transaction",
//     "subscriber": {
//         "country": "UG",
//         "currency": "UGX",
//         "msisdn": "12****89"
//     },
//     "transaction": {
//         "amount": 1000,
//         "country": "UG",
//         "currency": "UGX",
//         "id": "random-unique-id"
//     }
// }


//callback
// {
//     "transaction": {
//         "id": "BBZMiscxy",
//         "message": "Paid UGX 5,000 to TECHNOLOGIES LIMITED Charge UGX 140, Trans ID MP210603.1234.L06941.",
//         "status_code": "TS",
//         "airtel_money_id": "MP210603.1234.L06941"
//     }
// }


// DP00800001000
// Ambiguous	The transaction is still processing and is in ambiguous state. Please do the transaction enquiry to fetch the transaction status.
// DP00800001001
// Success	Transaction is successful.
// DP00800001002
// Incorrect Pin	Incorrect pin has been entered.
// DP00800001003
// Exceeds withdrawal amount limit(s) / Withdrawal amount limit exceeded	The User has exceeded their wallet allowed transaction limit.
// DP00800001004
// Invalid Amount	The amount User is trying to transfer is less than the minimum amount allowed.
// DP00800001005
// Transaction ID is invalid	User didn't enter the pin.
// DP00800001006
// In process	Transaction in pending state. Please check after sometime.
// DP00800001007
// Not enough balance	User wallet does not have enough money to cover the payable amount.
// DP00800001008
// Refused	The transaction was refused.
// DP00800001010
// Transaction not permitted to Payee	Payee is already initiated for churn or barred or not registered on Airtel Money platform.
// DP00800001024
// Transaction Timed Out	The transaction was timed out.
// DP00800001025




// refund    /standard/v1/payments/refund

// {
//     "transaction": {
//         "airtel_money_id": "CI************18"
//     }
// }


// {
//     "data": {
//         "transaction": {
//             "airtel_money_id": "CI2****29",
//             "status": "SUCCESS"
//         }
//     },
//     "status": {
//         "code": "200",
//         "message": "SUCCESS",
//         "result_code": "ESB000010",
//         "success": false
//     }
// }


//disbursement api /standard/v2/disbursements/
// curl -X POST https://openapiuat.airtel.africa/standard/v2/disbursements/
//   -H 'Content-Type: application/json'
//   -H 'Accept: */*'
//   -H 'X-Country: UG'
//   -H 'X-Currency: UGX'
//   -H 'Authorization: Bearer UCc*******x2w'
//   -H 'x-signature: MGsp*********Ag=='
//   -H 'x-key: DVZC***********NM='

// {
//     "payee": {
//         "currency": "KES",
//         "msisdn": "75****26",
//         "name": "Bob"
//     },
//     "reference": "AB***141",
//     "pin": "KYJ*****Rsa44",
//     "transaction": {
//         "amount": 1000,
//         "id": "AB***141",
//         "type": "B2B"
//     }
// }

// {
//     "data": {
//         "transaction": {
//             "airtel_money_id": "product-partner-**41",
//             "id": "AB***141",
//             "reference_id": "18****354",
//             "status": "TS"
//         }
//     },
//     "status": {
//         "code": "200",
//         "message": "Success",
//         "response_code": "DP00900001001",
//         "result_code": "ESB000010",
//         "success": true
//     }
// }
