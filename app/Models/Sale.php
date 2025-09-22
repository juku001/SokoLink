<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'seller_id',
        'sale_ref',
        'store_id',
        'order_id',
        'payment_id',
        'payment_method_id',
        'payment_type',
        'payment_option_id',
        'buyer_name',
        'amount',
        'sales_date',
        'sales_time',
        'status'
    ];

    public static function boot()
    {
        parent::boot();


        static::creating(function ($sale) {
            if (empty($sale->sale_ref)) {
                $sale->sale_ref = self::generateSaleReference();
            }
        });
    }


    public static function generateSaleReference()
    {
        return strtoupper(uniqid('SAL'));
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }


    public function saleProducts()
    {
        return $this->hasMany(SaleProduct::class);
    }


    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
