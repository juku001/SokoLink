<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Payment;
use Illuminate\Http\Request;

class AdminPaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['store', 'paymentMethod']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%");
            });
        }

        if ($request->filled('payment_method')) {
            $query->whereHas('paymentMethod', function ($q) use ($request) {
                $q->where('display', $request->payment_method);
            });
        }

        if ($request->filled('merchant')) {
            $query->whereHas('store', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->merchant}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $payments = $query->orderBy('updated_at', 'desc')->paginate(50);

        $data = $payments->map(function ($payment) {
            return [
                'id' => $payment->id,
                'ref' => $payment->reference,
                'txn_id' => $payment->transaction_id,
                'amount' => $payment->amount,
                'merchant' => $payment->store?->name,
                'method' => $payment->paymentMethod?->display,
                'status' => $payment->status,
                'time' => $payment->updated_at,
            ];
        });

        return ResponseHelper::success([
            'data' => $data,
            'pagination' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ]
        ], 'List of all payments');
    }



    public function show($id)
    {

        $payment = Payment::with(
            [
                'store',
                'user',
                'order',
                'paymentMethod',
                'paymentOption'
            ]
        )->find($id);

        if (!$payment) {
            return ResponseHelper::error([], 'Payment not found.', 404);
        }

        return ResponseHelper::success($payment, 'Payment details');


    }
}
