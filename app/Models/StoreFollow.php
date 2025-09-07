<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreFollow extends Model
{
    protected $fillable = ['store_id', 'buyer_id'];
}
