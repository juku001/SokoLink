<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreVisit extends Model
{
    protected $fillable = [
        'store_id',
        'user_id',
        'session_id',
        'ip_address',
    ];
}




// StoreVisit::create([
//     'store_id'   => $store->id,
//     'user_id'    => auth()->id(),       // null if guest
//     'session_id' => session()->getId(), // or Str::uuid()
//     'ip_address' => request()->ip(),
// ]);
