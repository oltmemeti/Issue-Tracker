<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserStory extends Model
{
    protected $fillable = ['title','description','priority','status','user_id','deadline'];

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function recomputeStatusFromTasks(): void
    {
        $hasTasks   = $this->tasks()->exists();
        $hasPending = $this->tasks()->where('status', '!=', 'done')->exists();

        $newStatus = 'new';
        if ($hasTasks) {
            $newStatus = $hasPending ? 'in_progress' : 'done';
        }

        if ($this->status !== $newStatus) {
            $this->status = $newStatus;
            $this->saveQuietly();
        }
    }
}
