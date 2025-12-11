<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreVisit extends Model
{
    protected $fillable = [
        'store_id',
        'seller_id',
        'user_id',
        'session_id',
        'ip_address',
    ];
}