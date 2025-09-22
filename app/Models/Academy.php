<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Academy extends Model
{
    protected $fillable = [
        'title',
        'subtitle'
    ];


    public function lessons()
    {
        return $this->hasMany(AcademyLesson::class);
    }



}
