<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'user_story_id','title','description',
        'acceptance_criteria','story_points',
        'priority','status'
    ];

    public function story()
    {
        return $this->belongsTo(UserStory::class, 'user_story_id');
    }
}