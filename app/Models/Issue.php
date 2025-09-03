<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Issue extends Model
{
    protected $fillable = [
        'title',
        'description',
        'type',
        'priority',
        'status',
        'user_id',
        'user_story_id',
        'task_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function story()
    {
        return $this->belongsTo(UserStory::class, 'user_story_id');
    }

    // optional link
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }
    public function comments()
    {
        return $this->hasMany(IssueComment::class);
    }
}
