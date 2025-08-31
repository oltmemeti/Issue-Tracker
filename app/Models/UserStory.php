<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserStory extends Model
{
    protected $fillable = ['title','description','priority','status'];

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
