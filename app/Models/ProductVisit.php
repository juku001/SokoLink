<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVisit extends Model
{
    protected $fillable = [
        "product_id",
        "user_id",
        "session_id",
        "ip_address"
    ];
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
