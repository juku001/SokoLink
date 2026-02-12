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


    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
