<?php

namespace App\Helpers;

use App\Models\PaymentMethod;
use App\Services\AirtelAPI;
use Illuminate\Support\Str;
use Selcom\ApigwClient\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

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
     * Handle MNO payments via Selcom.
     * Supports all Tanzanian mobile networks through Selcom's MOBILEMONEYPULL.
     */
    private static function handleMno(PaymentMethod $paymentMethod, array $data)
    {
        return self::initiateSelcomPayment($data);
    }

    /**
     * Initiate a Selcom mobile money push (USSD pull) payment.
     *
     * Flow:
     *  1. Create a checkout order on Selcom to obtain a transaction reference.
     *  2. Push the USSD prompt to the buyer's handset via the wallet-payment endpoint.
     *  3. Selcom calls the webhook once the buyer approves or rejects.
     */
    private static function initiateSelcomPayment(array $data)
    {
        if (empty($data['phone']) || empty($data['amount'])) {
            return [
                'status'  => false,
                'message' => 'Phone number and amount are required for mobile money payment.',
            ];
        }

        $user = Auth::user();

        // Selcom expects format 255XXXXXXXXX (no leading +)
        $phone = ltrim($data['phone'], '+');

        // A UUID that ties this transaction together across both Selcom calls
        $orderId = (string) Str::uuid();

        // Selcom requires the webhook URL Base64-encoded
        $webhook = base64_encode(env('SELCOM_WEBHOOK_URL'));

        $client = new Client(
            env('SELCOM_API_BASE_URL'),
            env('SELCOM_API_KEY'),
            env('SELCOM_API_SECRET')
        );

        $orderPayload = [
            'vendor'                    => env('SELCOM_VENDOR_TILL'),
            'order_id'                  => $orderId,
            'buyer_email'               => $user->email ?? 'noemail@sokolink.co.tz',
            'buyer_name'                => $user->name ?? 'Customer',
            'buyer_phone'               => $phone,
            'amount'                    => $data['amount'],
            'currency'                  => 'TZS',
            'no_of_items'               => 1,
            'payment_methods'           => 'MOBILEMONEYPULL',
            'webhook'                   => $webhook,
            'billing.firstname'         => $user->name ?? 'Customer',
            'billing.lastname'          => $user->name ?? 'Customer',
            'billing.address_1'         => 'Dar es Salaam',
            'billing.address_2'         => '',
            'billing.city'              => 'Dar es Salaam',
            'billing.state_or_region'   => 'Tanzania',
            'billing.postcode_or_pobox' => '0000',
            'billing.country'           => 'TZ',
            'billing.phone'             => $phone,
        ];

        Log::info('Selcom create-order payload', $orderPayload);

        $orderResponse = $client->postFunc(env('SELCOM_ORDER_ENDPOINT'), $orderPayload);

        Log::info('Selcom create-order response', (array) $orderResponse);

        if (empty($orderResponse['reference'])) {
            return [
                'status'  => false,
                'message' => $orderResponse['resultdesc'] ?? 'Failed to create Selcom order.',
                'data'    => $orderResponse,
            ];
        }

        $pushPayload = [
            'transid'  => $orderResponse['reference'],
            'order_id' => $orderId,
            'msisdn'   => $phone,
        ];

        Log::info('Selcom wallet-payment payload', $pushPayload);

        $pushResponse = $client->postFunc(env('SELCOM_WALLET_PAYMENT_ENDPOINT'), $pushPayload);

        Log::info('Selcom wallet-payment response', (array) $pushResponse);

        return [
            'status'    => true,
            'reference' => $orderId,
            'data'      => $pushResponse,
            'message'   => 'Payment push initiated. Please complete the payment on your phone.',
        ];
    }

    /**
     * Simulate Airtel Money payment.
     * Later you can replace with actual API integration.
     */
    private static function simulateAirtelPayment(array $data)
    {
        Log::info('simulate airedl');
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
        Log::info('airtel data' . json_encode($data));
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
