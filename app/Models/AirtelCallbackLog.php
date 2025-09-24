<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AirtelCallbackLog extends Model
{
    protected $fillable = [
        'payload',
        'payment_id',
        'reference',
        'airtel_money_id',
        'amount',
        'message',
        'status_code',
        'result',
        'status'
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
