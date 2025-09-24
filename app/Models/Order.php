<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'buyer_id',
        'total_amount',
        'shipping_cost',
        'status',
        'payment_option_id',
        'payment_method_id',
        'order_ref'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {

            $order->order_ref = 'ORD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        });

        static::created(function ($order) {

            $order->statusHistories()->create([
                'status' => 'pending',
                'note' => 'Order created with reference ' . $order->order_ref,
            ]);
        });

        static::updating(function ($order) {
            if ($order->isDirty('status')) {
                $originalStatus = $order->getOriginal('status');
                $newStatus = $order->status;

                $order->statusHistories()->create([
                    'status' => $newStatus,
                    'note' => "Status changed from {$originalStatus} to {$newStatus}"
                ]);
            }
        });
    }


    public function store()
    {
        return $this->belongsTo(Store::class);
    }



    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function address()
    {
        return $this->hasOne(Address::class);
    }
}
