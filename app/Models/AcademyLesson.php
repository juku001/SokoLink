<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademyLesson extends Model
{
    protected $fillable = [
        'academy_id',
        'title',
        'subtitle',
        'instructor',
        'ratings',
        'video_path',
        'duration'
    ];

    public function academy()
    {
        return $this->belongsTo(Academy::class);
    }
}
