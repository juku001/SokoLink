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
        'duration',
        'student_count'
    ];

    public function academy()
    {
        return $this->belongsTo(Academy::class);
    }

    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }
}
