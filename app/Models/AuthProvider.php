<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuthProvider extends Model
{
    protected $fillable = [
        'user_id', 
        'provider', 
        'is_active'
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }
}
