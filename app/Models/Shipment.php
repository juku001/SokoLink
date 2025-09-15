<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Str;


class Shipment extends Model
{
    protected $fillable = [
        'order_id',
        'seller_id',
        'address_id',
        'carrier',
        'tracking_number',
        'status',
        'shipped_at',
        'delivered_at'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($shipment) {
            if (empty($shipment->tracking_number)) {
                $shipment->tracking_number = static::generateTrackingNumber();
            }
        });
    }

    protected static function generateTrackingNumber()
    {
        do {
            $trackingNumber = 'TRK-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (self::where('tracking_number', $trackingNumber)->exists());

        return $trackingNumber;
    }

    public function items()
    {
        return $this->hasMany(ShipmentItem::class);
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }
}
