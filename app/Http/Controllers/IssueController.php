<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Models\Task;
use Illuminate\Http\Request;

class IssueController extends Controller
{
    public function index()
    {
        $issues = Issue::latest()->with('task', 'user')->get();
        return view('issues.index', compact('issues'));
    }

    public function create()
    {
        $tasks = Task::all();
        return view('issues.create', compact('tasks'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:bug,feature,improvement',
            'priority' => 'required|in:low,medium,high',
            'status' => 'required|in:open,in_progress,resolved',
            'task_id' => 'nullable|exists:tasks,id',
            'user_id' => 'nullable|exists:users,id', // match user story vibe
        ]);
    
        Issue::create($request->only([
            'title', 'description', 'type', 'priority', 'status', 'task_id', 'user_id'
        ]));
    
        return back()->with('success', 'Issue reported!');
    }
    
}