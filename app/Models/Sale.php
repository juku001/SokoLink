<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'seller_id',
        'store_id',
        'payment_method_id',
        'buyer_name',
        'amount',
        'sale_date',
        'sale_time',
        'status'
    ];



    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }


    public function products(){
        return $this->hasMany(SaleProduct::class,'sale_id');
    }


    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
