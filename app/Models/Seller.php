<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seller extends Model
{
    protected $fillable = [
        'payout_account',
        'payout_method',
        'user_id',
        'settlement',
        'active_store'
    ];

    public function payoutMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payout_method', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }


    public function store()
    {
        return $this->belongsTo(Store::class, 'active_store');
    }
}
