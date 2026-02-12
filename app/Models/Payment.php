<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
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


    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function paymentOption()
    {
        return $this->belongsTo(PaymentOptions::class);
    }
}
