<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryLedger extends Model
{
    protected $fillable = [
        'store_id',
        'product_id',
        'change',
        'balance',
        'reason'
    ];


    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function latestLedger()
    {
        return $this->hasOne(InventoryLedger::class, 'id', 'latest_ledger_id');
    }
}
