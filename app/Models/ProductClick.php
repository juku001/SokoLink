<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductClick extends Model
{
    protected $fillable = [
        'product_id'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
