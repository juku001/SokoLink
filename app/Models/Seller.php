<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seller extends Model
{
    protected $fillable = [
        'payout_account',
        'payout_method',
        'user_id'
    ];

    public function payoutMehod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payout_method', 'id');
    }
}
