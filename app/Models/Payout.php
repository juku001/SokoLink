<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    protected $fillable = [
        'ref_id',
        'txn_id',
        'seller_id',
        'payment_method_id',
        'payout_account',
        'currency',
        'amount',
        'status',
        'paid_at',
        'acknowledgement',
        'response',
        'data',
        'notes'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }


    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, );
    }
}
