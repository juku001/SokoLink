<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


/**
 * @OA\Schema(
 *     schema="Payment",
 *     type="object",
 *     title="Payment",
 *     required={"user_id","amount","status"},
 *     
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="order_id", type="integer", nullable=true, example=10),
 *     @OA\Property(property="user_id", type="integer", example=5),
 *     @OA\Property(property="store_id", type="integer", example=2),
 *     
 *     @OA\Property(property="amount", type="number", format="float", example=25000.50),
 *     @OA\Property(property="reference", type="string", example="PAY-2026-0001"),
 *     @OA\Property(property="transaction_id", type="string", example="TXN938475938"),
 *     
 *     @OA\Property(property="payment_method_id", type="integer", example=1),
 *     @OA\Property(property="payment_option_id", type="integer", example=2),
 *     
 *     @OA\Property(property="card_number", type="string", example="**** **** **** 1234"),
 *     @OA\Property(property="mm_yr", type="string", example="12/26"),
 *     @OA\Property(property="msisdn", type="string", example="255712345678"),
 *     
 *     @OA\Property(property="status", type="string", example="success"),
 *     @OA\Property(property="response_notes", type="string", example="Payment processed successfully"),
 *     @OA\Property(property="notes", type="string", example="Customer paid via mobile money"),
 *     
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-02-12T08:30:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-02-12T08:35:00Z")
 * )
 */

class Payment extends Model
{
    protected $fillable = [
        'cart_id',
        'user_id',
        'store_id',
        'amount',
        'reference',
        'transaction_id',
        'payment_method_id',
        'payment_option_id',
        'card_number',
        'mm_yr',
        'msisdn',
        'status',
        'response_notes',
        'notes'

    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }


    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function paymentOption()
    {
        return $this->belongsTo(PaymentOptions::class);
    }
}
