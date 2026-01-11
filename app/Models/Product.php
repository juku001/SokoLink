<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'store_id',
        'name',
        'slug',
        'description',
        'category_id',
        'price',
        'sku',
        'barcode',
        'is_online',
        'stock_qty',
        'sold_count',
        'low_stock_threshold',
    ];

    protected static function booted()
    {

        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = static::generateSlug($product->name);
            }
        });

        static::updating(function ($product) {
            if ($product->isDirty('name')) {
                $product->slug = static::generateSlug($product->name);
            }
        });


        static::deleting(function ($product) {

            $product->images->each(function ($image) {
                if ($image->path && \Storage::disk('public')->exists($image->path)) {
                    \Storage::disk('public')->delete($image->path);
                }
                $image->delete();
            });
            $product->reviews()->delete();
            $product->sales()->delete();
        });
    }

    protected $appends = ['stock_status'];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function sales()
    {
        return $this->hasMany(SaleProduct::class, 'product_id');
    }


    public function getStockStatusAttribute(): string
    {
        $qty = max(0, $this->stock_qty ?? 0);
        $threshold = $this->low_stock_threshold ?? 0;

        if ($qty <= 0) {
            return 'out_of_stock';
        }

        if ($threshold > 0 && $qty <= $threshold) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    protected static function generateSlug($name)
    {
        $slug = Str::slug($name);
        $count = static::where('slug', 'LIKE', "{$slug}%")->count();

        return $count ? "{$slug}-" . ($count + 1) : $slug;
    }

}
