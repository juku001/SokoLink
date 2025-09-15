<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Escrow extends Model
{
    protected $fillable = [
        'order_id',
        'buyer_id',
        'seller_id',
        'total_amount',
        'seller_amount',
        'platform_fee',
        'payment_id',
        'status',
        'released_at',
        'refunded_at'
    ];
}
