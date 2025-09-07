<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupContact extends Model
{
    protected $fillable = [
        'group_id',
        'contact_id',
        'user_id'
    ];
}
