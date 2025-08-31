<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserStory extends Model
{
    protected $fillable = ['title','description','priority','status', 'user_id','deadline'];

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
    public function user()
{
    return $this->belongsTo(User::class);
}
}
