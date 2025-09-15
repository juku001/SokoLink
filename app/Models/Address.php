<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'type',
        'fullname',
        'street',
        'region_id',
        'country_id',
        'postal_code',
        'phone'
    ];


    public function order()
    {
        return $this->belongsTo(Order::class);
    }



    public function user()
    {

        return $this->belongsTo(User::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }
}
