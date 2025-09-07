<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = [
        'name',
        'abbr'
    ];

    public function regions()
    {
        return $this->hasMany(Region::class);
    }
}
