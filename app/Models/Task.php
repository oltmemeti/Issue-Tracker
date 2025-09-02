<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'user_story_id','title','description','acceptance_criteria',
        'story_points','priority','status','user_id'
    ];

    public function story()
    {
        return $this->belongsTo(UserStory::class, 'user_story_id');
    }

    protected static function booted()
    {
        $recompute = function (Task $task) {
            // Recompute for the *current* parent story
            if ($task->relationLoaded('story')) {
                optional($task->story)->recomputeStatusFromTasks();
            } else {
                optional($task->story()->first())->recomputeStatusFromTasks();
            }
            if ($task->wasChanged('user_story_id')) {
                $oldId = $task->getOriginal('user_story_id');
                if ($oldId) {
                    $oldStory = UserStory::find($oldId);
                    optional($oldStory)->recomputeStatusFromTasks();
                }
            }
        };

        static::created($recompute);
        static::updated($recompute);
        static::deleted($recompute);
    }
}