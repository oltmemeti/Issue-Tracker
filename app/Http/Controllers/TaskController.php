<?php

namespace App\Http\Controllers;

use App\Models\UserStory;
use App\Models\Task;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Http\Request;



class TaskController extends Controller
{
    public function index()
    {
        $columnLabels = [
            'new'          => 'New',
            'in_progress'  => 'In Progress',
            'blocked'      => 'Blocked',
            'ready_for_qa' => 'Ready for QA',
            'done'         => 'Done',
        ];
    
        $users = User::select('id','name')->orderBy('name')->get();
    
        $issueColumnLabels = [
            'open'        => 'Open',
            'in_progress' => 'In Progress',
            'resolved'    => 'Resolved',
        ];
    
        $issues = Issue::with(['story','task','user'])->latest()->get();
        $issuesByStatus = $issues->groupBy('status');
    
        $allTasks = Task::select('id','title')->orderBy('title')->get();
    
        // ✅ Split stories
        $activeStories = UserStory::with(['tasks' => fn($q)=>$q->latest(), 'user'])
            ->where('status', '!=', 'done')
            ->latest()->get();
    
        $doneStories = UserStory::with(['tasks' => fn($q)=>$q->latest(), 'user'])
            ->where('status', 'done')
            ->latest()->get();
    
        return view('dashboard', compact(
            'activeStories',
            'doneStories',
            'columnLabels',
            'users',
            'issueColumnLabels',
            'issuesByStatus',
            'allTasks'
        ));
    }
    public function updateStatus(Request $request, Task $task)
{
    $data = $request->validate([
        'status' => 'required|in:new,in_progress,blocked,ready_for_qa,done',
    ]);

    $task->update(['status' => $data['status']]);

    optional($task->story)->recomputeStatusFromTasks();

    return response()->json([
        'ok'          => true,
        'task_id'     => $task->id,
        'status'      => $task->status,
        'story_id'    => $task->user_story_id,
        'story_status'=> optional($task->story)->status,
    ]);
}

}
