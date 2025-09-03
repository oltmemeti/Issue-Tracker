<?php

namespace App\Http\Controllers;

use App\Models\UserStory;
use App\Models\Task;
use App\Models\Issue;
use App\Models\User;
use App\Models\Tag;
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

        // Eager-load tags to avoid N+1 in the Issues board
        $issues = Issue::with(['story','task','user','tags'])->latest()->get();
        $issuesByStatus = $issues->groupBy('status');

        $allTasks = Task::select('id','title')->orderBy('title')->get();

        // Stories split
        $activeStories = UserStory::with(['tasks' => fn($q)=>$q->latest(), 'user'])
            ->where('status', '!=', 'done')
            ->latest()->get();

        $doneStories = UserStory::with(['tasks' => fn($q)=>$q->latest(), 'user'])
            ->where('status', 'done')
            ->latest()->get();

        // ✅ Define $tags for the Issue modal
        $tags = Tag::orderBy('name')->get(['id','name','color']);

        return view('dashboard', compact(
            'activeStories',
            'doneStories',
            'columnLabels',
            'users',
            'issueColumnLabels',
            'issuesByStatus',
            'allTasks',
            'tags'
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
            'ok'           => true,
            'task_id'      => $task->id,
            'status'       => $task->status,
            'story_id'     => $task->user_story_id,
            'story_status' => optional($task->story)->status,
        ]);
    }

    public function update(Request $request, Task $task)
    {
        $data = $request->validate([
            'user_story_id'       => 'required|exists:user_stories,id',
            'title'               => 'required|string|max:255',
            'description'         => 'nullable|string',
            'acceptance_criteria' => 'nullable|string',
            'story_points'        => 'nullable|in:1,2,3,5,8',
            'priority'            => 'required|in:low,medium,high',
            'status'              => 'required|in:new,in_progress,blocked,ready_for_qa,done',
            'user_id'             => 'nullable|exists:users,id',
        ]);

        $task->update($data);

        optional($task->story)->recomputeStatusFromTasks();

        return response()->json(['ok' => true]);
    }
}