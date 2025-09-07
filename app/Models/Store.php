<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $fillable = [

        'seller_id',
        'name',
        'slug',
        'category_id',
        'description',
        'is_online',
        'contact_mobile',
        'contact_email',
        'whatsapp',
        'shipping_origin',
        'rating_avg',
        'rating_count',
        'region_id',
        'address'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function followers()
    {
        return $this->hasMany(StoreFollow::class, 'store_id');
    }

}
