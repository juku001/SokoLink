<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{

    protected $fillable = [
        'store_id',
        'name',
        'slug',
        'description',
        'price',
        'sku',
        'barcode',
        'is_online',
        'stock_qty',
        'sold_count',
    ];




    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }


}
