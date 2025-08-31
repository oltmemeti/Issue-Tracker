<?php

namespace App\Http\Controllers;

use App\Models\UserStory;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
public function index()
{
    // Real data from DB only; eager-load tasks
    $stories = UserStory::with(['tasks' => fn ($q) => $q->latest('created_at')])
        ->latest('created_at')
        ->get();

    // Option A: pass labels so Blade stays tidy (not fake data—just static UI text)
$columnLabels = [
    'new'          => 'New',
    'in_progress'  => 'In Progress',
    'blocked'      => 'Blocked',
    'ready_for_qa' => 'Ready for QA',
    'done'         => 'Done',
];

    return view('dashboard', compact('stories', 'columnLabels'));
}


    public function store(Request $request) // (task create)
    {
        Task::create($request->only([
            'user_story_id','title','description','acceptance_criteria',
            'story_points','priority','status',
        ]));

        return back()->with('success', 'Task created!');
    }
public function update(Request $request, Task $task)
{
    $validated = $request->validate([
        'user_story_id'        => ['required','exists:user_stories,id'],
        'title'                => ['required','string','max:255'],
        'description'          => ['nullable','string'],
        'acceptance_criteria'  => ['nullable','string'],
        'story_points'         => ['nullable','integer','in:1,2,3,5,8'],
        'priority'             => ['required','in:low,medium,high'],
        'status'               => ['required','in:new,in_progress,blocked,ready_for_qa,done'],
    ]);

    $task->update($validated);

    return back()->with('success', 'Task updated!');
}
}
