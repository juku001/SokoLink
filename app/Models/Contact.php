<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $fillable = [
        'name',
        'store_id',
        'mobile',
        'email',
        'whatsapp',
        'tags',
        'type',
        'last_interaction_at',
        'user_id'
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function store(){
        return $this->belongsTo(Store::class);
    }
}
