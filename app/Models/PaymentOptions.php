<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentOptions extends Model
{

    protected $table = "payment_options";

    protected $fillable = [
        'name',
        'key',
        'description'
    ];
}
