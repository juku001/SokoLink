<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Str;

class Store extends Model
{
    protected $fillable = [

        'seller_id',
        'name',
        'slug',
        'category_id',
        'description',
        'is_online',
        'thumbnail',
        'subtitle',
        'contact_mobile',
        'contact_email',
        'whatsapp',
        'shipping_origin',
        'rating_avg',
        'rating_count',
        'region_id',
        'address'
    ];


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($store) {
            if (empty($store->slug)) {
                $store->slug = static::generateSlug($store->name);
            }
        });

        static::updating(function ($store) {
            if ($store->isDirty('name')) {
                $store->slug = static::generateSlug($store->name);
            }
        });
    }
    /**
     * Generate a unique slug.
     */
    protected static function generateSlug($name)
    {
        $slug = Str::slug($name);
        $count = static::where('slug', 'LIKE', "{$slug}%")->count();

        return $count ? "{$slug}-" . ($count + 1) : $slug;
    }



    public function user()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }


    public function sales()
    {
        return $this->hasMany(Sale::class, '');
    }



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


    public function region()
    {
        return $this->belongsTo(Region::class);
    }
}
