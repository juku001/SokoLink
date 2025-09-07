<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Review extends Model
{
    protected $fillable = [
        'user_id',
        'rating',
        'review',
        'is_verified_purchase'
    ];

    // Polymorphic relation
    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    // Reviewer
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
