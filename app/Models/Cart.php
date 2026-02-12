<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = [
        'buyer_id'
    ];

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }


    public function order()
    {
        return $this->hasOne(Order::class);
    }
}
